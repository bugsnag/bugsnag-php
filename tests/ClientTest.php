<?php

namespace Bugsnag\Tests;

use Bugsnag\Client;
use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\Report;
use Bugsnag\Shutdown\PhpShutdownStrategy;
use Exception;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\Uri;
use Mockery;
use ReflectionClass;

class ClientTest extends TestCase
{
    protected $guzzle;
    protected $config;
    protected $client;

    protected function setUp()
    {
        $this->guzzle = $this->getMockBuilder(Guzzle::class)
                             ->setMethods([self::getGuzzleMethod()])
                             ->getMock();

        $this->client = $this->getMockBuilder(Client::class)
                             ->setMethods(['notify'])
                             ->setConstructorArgs([$this->config = new Configuration('example-api-key'), null, $this->guzzle])
                             ->getMock();
    }

    public function testManualErrorNotification()
    {
        $this->client->expects($this->once())->method('notify');

        $this->client->notifyError('SomeError', 'Some message');
    }

    public function testManualErrorNotificationWithSeverity()
    {
        $client = new Client($this->config, null, $this->guzzle);
        $prop = (new ReflectionClass($client))->getProperty('http');
        $prop->setAccessible(true);

        $http = $this->getMockBuilder(HttpClient::class)
                     ->setMethods(['queue'])
                     ->setConstructorArgs([$this->config, $this->guzzle])
                     ->getMock();
        $prop->setValue($client, $http);

        $http->expects($this->once())
             ->method('queue')
             ->with($this->callback(function ($subject) {
                 return $subject->getSeverity() === 'info';
             }));

        $client->notifyError('SomeError', 'Some message', function ($report) {
            $report->setSeverity('info');
        });
    }

    public function testManualExceptionNotification()
    {
        $this->client->expects($this->once())->method('notify');

        $this->client->notifyException(new Exception('Something broke'));
    }

    public function testManualExceptionNotificationWithSeverity()
    {
        $this->client->expects($this->once())->method('notify');

        $this->client->notifyException(new Exception('Something broke'), function ($report) {
            $report->setSeverity('info');
        });
    }

    protected function getGuzzle(Client $client)
    {
        $prop = (new ReflectionClass($client))->getProperty('http');
        $prop->setAccessible(true);

        $http = $prop->getValue($client);

        $prop = (new ReflectionClass($http))->getProperty('guzzle');
        $prop->setAccessible(true);

        return $prop->getValue($http);
    }

    public function testDefaultSetup()
    {
        $this->assertEquals(
            new Uri('https://notify.bugsnag.com'),
            self::getGuzzleBaseUri($this->getGuzzle(Client::make('123')))
        );
    }

    public function testCanMake()
    {
        $client = Client::make('123', 'https://example.com');

        $this->assertInstanceOf(Client::class, $client);

        $this->assertEquals(
            new Uri('https://example.com'),
            self::getGuzzleBaseUri($this->getGuzzle($client))
        );
    }

    public function testCanMakeFromEnv()
    {
        try {
            putenv('BUGSNAG_API_KEY=foo-baz');
            putenv('BUGSNAG_ENDPOINT=http://foo.com');

            $client = Client::make();

            $this->assertInstanceOf(Client::class, $client);

            $this->assertEquals(
                new Uri('http://foo.com'),
                self::getGuzzleBaseUri($this->getGuzzle($client))
            );
        } finally {
            putenv('BUGSNAG_API_KEY=');
            putenv('BUGSNAG_ENDPOINT=');
        }
    }

    public function testCanMakeFromEnvSuperglobal()
    {
        try {
            $_ENV['BUGSNAG_API_KEY'] = 'foo-bar';
            $_ENV['BUGSNAG_ENDPOINT'] = 'http://bar.com';

            $client = Client::make();

            $this->assertInstanceOf(Client::class, $client);

            $this->assertEquals(
                new Uri('http://bar.com'),
                self::getGuzzleBaseUri($this->getGuzzle($client))
            );
        } finally {
            unset($_ENV['BUGSNAG_API_KEY']);
            unset($_ENV['BUGSNAG_ENDPOINT']);
        }
    }

    public function testBeforeNotifySkipsError()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->setBatchSending(false);

        $this->client->registerCallback(function (Report $report) {
            if ($report->getName() === 'SkipMe') {
                return false;
            }
        });

        $this->guzzle->expects($this->never())->method(self::getGuzzleMethod());

        $this->client->notify(Report::fromNamedError($this->config, 'SkipMe', 'Message'));
    }

    public function testBeforeNotifyCanModifyReportFrames()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->setBatchSending(false);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Magic', 'oh no'));

        $this->assertFalse($report->getStacktrace()->getFrames()[0]['inProject']);

        $this->client->registerCallback(function (Report $report) {
            $frames = &$report->getStacktrace()->getFrames();
            $frames[0]['inProject'] = true;

            return true;
        });

        $this->client->notify($report = Report::fromNamedError($this->config, 'Magic', 'oh no'));

        $this->assertTrue($report->getStacktrace()->getFrames()[0]['inProject']);
    }

    public function testDirectCallbackSkipsError()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->setBatchSending(false);

        $this->guzzle->expects($this->never())->method(self::getGuzzleMethod());

        $this->client->notify(Report::fromNamedError($this->config, 'SkipMe', 'Message'), function (Report $report) {
            if ($report->getName() === 'SkipMe') {
                return false;
            }
        });
    }

    public function testMetaDataWorks()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'), function ($report) {
            $report->setMetaData(['foo' => 'baz']);
        });

        $this->assertSame(['foo' => 'baz'], $report->getMetaData());
    }

    public function testCustomMiddlewareWorks()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->registerMiddleware(function ($report, callable $next) {
            $report->setMetaData(['middleware' => 'registered']);
            $next($report);
        });

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $this->assertSame(['middleware' => 'registered'], $report->getMetaData());
    }

    public function testMiddlewareCanModifySeverity()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->registerMiddleware(function ($report, callable $next) {
            $report->setSeverity('info');
            $next($report);
        });

        $report = Report::fromNamedError($this->config, 'Name');
        $report->setSeverity('error');

        $this->client->notify($report);

        $this->assertSame('info', $report->getSeverity());
    }

    public function testMiddlewareCanModifyUnhandled()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->registerMiddleware(function ($report, callable $next) {
            $report->setUnhandled(true);
            $next($report);
        });

        $report = Report::fromNamedError($this->config, 'Name');
        $report->setUnhandled(false);

        $this->client->notify($report);

        $this->assertSame(true, $report->getUnhandled());
    }

    public function testMiddlewareCanModifySeverityReason()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->registerMiddleware(function ($report, callable $next) {
            $report->setSeverityReason([
                'type' => 'right',
            ]);
            $next($report);
        });

        $report = Report::fromNamedError($this->config, 'Name');
        $report->setSeverityReason([
            'type' => 'wrong',
        ]);

        $this->client->notify($report);

        $this->assertSame(['type' => 'right'], $report->getSeverityReason());
    }

    public function testNotOverriddenByCallbacks()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->registerMiddleware(function ($report, callable $next) {
            $report->setUnhandled(true);
            $report->setSeverityReason([
                'type' => 'right',
            ]);
            $next($report);
        });

        $report = Report::fromNamedError($this->config, 'Name');
        $report->setUnhandled(false);
        $report->setSeverityReason([
            'type' => 'wrong',
        ]);

        $this->client->notify($report, function ($report) {
            $report->setUnhandled(false);
            $report->setSeverityReason([
                'type' => 'wrong',
            ]);
        });

        $this->assertSame(['type' => 'right'], $report->getSeverityReason());
        $this->assertSame(true, $report->getUnhandled());
    }

    public function testBreadcrumbsWorks()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->leaveBreadcrumb('Test', 'user', ['foo' => 'bar']);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(4, $breadcrumbs[0]);
        $this->assertInternalType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Test', $breadcrumbs[0]['name']);
        $this->assertSame('user', $breadcrumbs[0]['type']);
        $this->assertSame(['foo' => 'bar'], $breadcrumbs[0]['metaData']);
    }

    public function testBreadcrumbsFallback()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->leaveBreadcrumb('Foo Bar Baz', 'bla');

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(3, $breadcrumbs[0]);
        $this->assertInternalType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Foo Bar Baz', $breadcrumbs[0]['name']);
        $this->assertSame('manual', $breadcrumbs[0]['type']);
        $this->assertFalse(isset($breadcrumbs[0]['metaData']));
    }

    public function testBreadcrumbsNoName()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        // notify with an empty report name
        $this->client->notify(Report::fromNamedError($this->config, ''));

        // then notify again to pickup the previous breadcrumb
        $this->client->notify($report = Report::fromNamedError($this->config, 'foo'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(4, $breadcrumbs[0]);
        $this->assertInternalType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Error', $breadcrumbs[0]['name']);
        $this->assertSame('error', $breadcrumbs[0]['type']);
        $this->assertTrue(isset($breadcrumbs[0]['metaData']));
    }

    public function testBreadcrumbsGetShortNameClass()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->leaveBreadcrumb(Client::class, 'state');

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(3, $breadcrumbs[0]);
        $this->assertInternalType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Client', $breadcrumbs[0]['name']);
        $this->assertSame('state', $breadcrumbs[0]['type']);
        $this->assertFalse(isset($breadcrumbs[0]['metaData']));
    }

    public function testBreadcrumbsLarge()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->leaveBreadcrumb('Test', 'user', ['foo' => str_repeat('A', 5000)]);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(3, $breadcrumbs[0]);
        $this->assertInternalType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Test', $breadcrumbs[0]['name']);
        $this->assertSame('user', $breadcrumbs[0]['type']);
        $this->assertFalse(isset($breadcrumbs[0]['metaData']));
    }

    public function testBreadcrumbsAgain()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->leaveBreadcrumb('Test', 'user', ['foo' => 'bar']);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(4, $breadcrumbs[0]);
        $this->assertInternalType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Test', $breadcrumbs[0]['name']);
        $this->assertSame('user', $breadcrumbs[0]['type']);
        $this->assertSame(['foo' => 'bar'], $breadcrumbs[0]['metaData']);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(2, $breadcrumbs);

        $this->assertCount(4, $breadcrumbs[0]);
        $this->assertInternalType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Test', $breadcrumbs[0]['name']);
        $this->assertSame('user', $breadcrumbs[0]['type']);
        $this->assertSame(['foo' => 'bar'], $breadcrumbs[0]['metaData']);

        $this->assertCount(4, $breadcrumbs[1]);
        $this->assertInternalType('string', $breadcrumbs[1]['timestamp']);
        $this->assertSame('Name', $breadcrumbs[1]['name']);
        $this->assertSame('error', $breadcrumbs[1]['type']);
        $this->assertSame(['name' => 'Name', 'severity' => 'warning'], $breadcrumbs[1]['metaData']);

        $this->client->clearBreadcrumbs();

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(0, $breadcrumbs);
    }

    public function testNoEnvironmentByDefault()
    {
        $_ENV['SOMETHING'] = 'blah';

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->registerDefaultCallbacks();

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $this->assertArrayNotHasKey('Environment', $report->getMetaData());
    }

    public function testBatchingDoesNotFlush()
    {
        $this->client = $this->getMockBuilder(Client::class)
                             ->setMethods(['flush'])
                             ->setConstructorArgs([$this->config, null, $this->guzzle])
                             ->getMock();

        $this->client->expects($this->never())->method('flush');

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));
    }

    public function testFlushesWhenNotBatching()
    {
        $this->client = $this->getMockBuilder(Client::class)
                             ->setMethods(['flush'])
                             ->setConstructorArgs([$this->config, null, $this->guzzle])
                             ->getMock();

        $this->client->expects($this->once())->method('flush');

        $this->client->setBatchSending(false);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));
    }

    public function testCanManuallyFlush()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->setBatchSending(false);

        $this->guzzle->expects($this->once())->method(self::getGuzzleMethod());

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $this->client->flush();
        $this->client->flush();
        $this->client->flush();
    }

    public function testDeployWorksOutOfTheBox()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy();
    }

    public function testDeployWorksWithReleaseStage()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'staging', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');
        $this->config->setReleaseStage('staging');

        $this->client->deploy();
    }

    public function testDeployWorksWithAppVersion()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'production', 'appVersion' => '1.1.0', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy();
    }

    public function testDeployWorksWithRepository()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['sourceControl' => ['repository' => 'foo'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy('foo');
    }

    public function testDeployWorksWithBranch()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy(null, 'master');
    }

    public function testDeployWorksWithRevision()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['sourceControl' => ['revision' => 'bar'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy(null, null, 'bar');
    }

    public function testDeployWorksWithEverything()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['sourceControl' => ['repository' => 'baz', 'revision' => 'foo'], 'releaseStage' => 'development', 'appVersion' => '1.3.1', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setReleaseStage('development');
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy('baz', 'develop', 'foo');
    }

    public function testBuildWorksOutOfTheBox()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build();
    }

    public function testBuildWorksWithReleaseStage()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'staging', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');
        $this->config->setReleaseStage('staging');

        $this->client->build();
    }

    public function testBuildWorksWithRepository()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['sourceControl' => ['repository' => 'foo'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build('foo');
    }

    public function testBuildWorksWithProvider()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['sourceControl' => ['provider' => 'github'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, null, 'github');
    }

    public function testBuildWorksWithRevision()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['sourceControl' => ['revision' => 'bar'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, 'bar');
    }

    public function testBuildWorksWithBuilderName()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['builderName' => 'me', 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, null, null, 'me');
    }

    public function testBuildWorksWithBuildTool()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, null, null, null);
    }

    public function testBuildWorksWithEverything()
    {
        $this->guzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['builderName' => 'me', 'sourceControl' => ['repository' => 'baz', 'revision' => 'foo', 'provider' => 'github'], 'releaseStage' => 'development', 'appVersion' => '1.3.1', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setReleaseStage('development');
        $this->config->setAppVersion('1.3.1');

        $this->client->build('baz', 'foo', 'github', 'me');
    }

    public function testBuildFailsWithoutAPIKey()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        // Setup error_log mocking
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: App version is not set. Unable to send build report.'));

        $this->client->build();
    }

    public function testSeverityReasonUnmodified()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $event = $report->toArray();

        $this->assertSame($event['severityReason'], ['type' => 'handledError']);
    }

    public function testSeverityModifiedByCallback()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $report = Report::fromNamedError($this->config, 'Name');

        $this->client->notify($report, function ($report) {
            $report->setSeverity('info');
        });

        $event = $report->toArray();

        $this->assertSame($event['severity'], 'info');
        $this->assertSame($event['severityReason'], ['type' => 'userCallbackSetSeverity']);
    }

    public function testSeverityReasonNotModifiedByCallback()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $report = Report::fromNamedError($this->config, 'Name', null, false, ['type' => 'handledError']);

        $this->client->notify($report, function ($report) {
            $report->setSeverityReason(['type' => 'not a type']);
        });

        $event = $report->toArray();

        $this->assertSame($event['severityReason'], ['type' => 'handledError']);
    }

    public function testUrlModifiableByCallback()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';
        $_SERVER['HTTP_HOST'] = 'test.com';
        $_SERVER['REQUEST_URI'] = '/blah/blah.php?user=anon&password=hunter2';

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->client->registerDefaultCallbacks();

        $report = Report::fromNamedError($this->config, 'Name');

        $this->client->notify($report, function ($report) {
            $this->assertSame('http://test.com/blah/blah.php?user=anon&password=hunter2', $report->getMetaData()['request']['url']);
            $report->addMetaData(['request' => ['url' => 'REDACTED']]);
        });

        $this->assertSame('REDACTED', $report->getMetaData()['request']['url']);
    }

    public function testBatchSending()
    {
        $client = Client::make('foo');

        $this->assertTrue($client->isBatchSending());

        $this->assertSame($client, $client->setBatchSending(false));

        $this->assertFalse($client->isBatchSending());
    }

    public function testGetApiKey()
    {
        $client = Client::make('foo');
        $this->assertSame('foo', $client->getApiKey());
    }

    public function testNotifyReleaseStages()
    {
        $client = Client::make('foo');
        $this->assertSame($client, $client->setNotifyReleaseStages(null));
        $this->assertSame($client, $client->setReleaseStage('beta'));
        $this->assertTrue($client->shouldNotify());
        $this->assertSame($client, $client->setNotifyReleaseStages(['prod']));
        $this->assertFalse($client->shouldNotify());
        $this->assertSame($client, $client->setNotifyReleaseStages(['prod', 'beta']));
        $this->assertTrue($client->shouldNotify());
    }

    public function testFilters()
    {
        $client = Client::make('foo');
        $this->assertSame($client, $client->setFilters(['pass']));
        $this->assertSame(['pass'], $client->getFilters());
    }

    public function testSetProjectRoot()
    {
        $client = Client::make('foo');
        $client->setProjectRoot('/foo/bar');
        $this->assertTrue($client->isInProject('/foo/bar/z'));
        $this->assertFalse($client->isInProject('/foo/baz'));
        $this->assertFalse($client->isInProject('/foo'));
    }

    public function testSetProjectRootRegex()
    {
        $client = Client::make('foo');
        $client->setProjectRootRegex('/^\/foo\/bar/i');
        $this->assertTrue($client->isInProject('/foo/bar/z'));
        $this->assertFalse($client->isInProject('/foo/baz'));
        $this->assertFalse($client->isInProject('/foo'));
    }

    public function testSetStripPath()
    {
        $client = Client::make('foo');
        $client->setStripPath('/foo/bar/');
        $this->assertSame('src/thing.php', $client->getStrippedFilePath('/foo/bar/src/thing.php'));
        $this->assertSame('/foo/src/thing.php', $client->getStrippedFilePath('/foo/src/thing.php'));
        $this->assertSame('x/src/thing.php', $client->getStrippedFilePath('x/src/thing.php'));
    }

    public function testSetStripPathRegex()
    {
        $client = Client::make('foo');
        $client->setStripPathRegex('/^\\/(foo|bar)\\//');
        $this->assertSame('src/thing.php', $client->getStrippedFilePath('/foo/src/thing.php'));
        $this->assertSame('src/thing.php', $client->getStrippedFilePath('/bar/src/thing.php'));
        $this->assertSame('/baz/src/thing.php', $client->getStrippedFilePath('/baz/src/thing.php'));
        $this->assertSame('x/foo/thing.php', $client->getStrippedFilePath('x/foo/thing.php'));
    }

    public function testSendCode()
    {
        $client = Client::make('foo');
        $this->assertTrue($client->shouldSendCode());
        $this->assertSame($client, $client->setSendCode(true));
        $this->assertTrue($client->shouldSendCode());
        $this->assertSame($client, $client->setSendCode(false));
        $this->assertFalse($client->shouldSendCode());
    }

    public function testBuildEndpoint()
    {
        $client = Client::make('foo');
        $this->assertSame($client, $client->setBuildEndpoint('https://example'));
        $this->assertSame('https://example', $client->getBuildEndpoint());
    }

    public function testSessionClient()
    {
        $client = Client::make('foo');
        $this->assertSame($client, $client->setSessionEndpoint('https://example'));
        $sessionClient = $client->getSessionClient();
        $this->assertEquals(new Uri('https://example'), self::getGuzzleBaseUri($sessionClient));
    }

    public function testSetAutoCaptureSessions()
    {
        $client = Client::make('foo');
        $this->assertSame($client, $client->setAutoCaptureSessions(false));
        $this->assertFalse($client->shouldCaptureSessions());
        $this->assertSame($client, $client->setAutoCaptureSessions(true));
        $this->assertTrue($client->shouldCaptureSessions());
    }

    public function testErrorReportingLevel()
    {
        $client = Client::make('foo');
        $this->assertSame($client, $client->setErrorReportingLevel(E_ALL));
        $this->assertFalse($client->shouldIgnoreErrorCode(E_NOTICE));
        $this->assertSame($client, $client->setErrorReportingLevel(E_ALL && ~E_NOTICE));
        $this->assertTrue($client->shouldIgnoreErrorCode(E_NOTICE));
    }

    public function testMetaData()
    {
        $client = Client::make('foo');
        $this->assertSame($client, $client->setMetaData(['foo' => ['bar' => 'baz']]));
        $this->assertSame(['foo' => ['bar' => 'baz']], $client->getMetaData());
        $this->assertSame($client, $client->setMetaData(['foo' => ['bear' => 4]]));
        $this->assertSame(['foo' => ['bar' => 'baz', 'bear' => 4]], $client->getMetaData());
        $this->assertSame($client, $client->setMetaData(['baz' => ['bar' => 9]], false));
        $this->assertSame(['baz' => ['bar' => 9]], $client->getMetaData());
    }

    public function testDeviceData()
    {
        $client = Client::make('foo');
        $this->assertSame($client, $client->setHostname('web1.example.com'));
    }

    public function testAppData()
    {
        $client = Client::make('foo');
        $this->assertSame($client, $client->setAppVersion('34.2.1-beta2'));
        $data = $client->getAppData();
        $this->assertSame('34.2.1-beta2', $client->getAppData()['version']);
        $client->setFallbackType('foo-app');
        $this->assertSame('foo-app', $client->getAppData()['type']);
    }

    public function testNotifier()
    {
        $client = Client::make('foo');
        $this->assertSame($client, $client->setNotifier(['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], $client->getNotifier());
    }

    public function testShutdownStrategyIsCalledWithinConstructor()
    {
        $mockShutdown = Mockery::mock(PhpShutdownStrategy::class);
        $mockShutdown->shouldReceive('registerShutdownStrategy')->once();
        new Client($this->config, null, null, $mockShutdown);
    }

    private function guzzlePostWith($uri, array $options = [])
    {
        $method = self::getGuzzleMethod();
        $mock = $this->guzzle->expects($this->once())->method($method);

        if ($method === 'request') {
            return $mock->with($this->equalTo('POST'), $this->equalTo($uri), $this->equalTo($options));
        }

        return $mock->with($this->equalTo($uri), $this->equalTo($options));
    }
}
