<?php

namespace Bugsnag\Tests;

use Bugsnag\Client;
use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\Report;
use Exception;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Uri;
use phpmock\phpunit\PHPMock;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;

class ClientTest extends TestCase
{
    use PHPMock;

    protected $guzzle;
    protected $config;
    protected $client;

    protected function setUp()
    {
        $this->guzzle = $this->getMockBuilder(Guzzle::class)
                             ->setMethods(['post'])
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
        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $this->assertEquals(new Uri('https://notify.bugsnag.com'), $this->getGuzzle(Client::make('123'))->getConfig('base_uri'));
        } else {
            $this->assertSame('https://notify.bugsnag.com', $this->getGuzzle(Client::make('123'))->getBaseUrl());
        }
    }

    public function testCanMake()
    {
        $client = Client::make('123', 'https://example.com');

        $this->assertInstanceOf(Client::class, $client);

        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $this->assertEquals(new Uri('https://example.com'), $this->getGuzzle($client)->getConfig('base_uri'));
        } else {
            $this->assertSame('https://example.com', $this->getGuzzle($client)->getBaseUrl());
        }
    }

    public function testCanMakeFromEnv()
    {
        $env = $this->getFunctionMock('Bugsnag', 'getenv');
        $env->expects($this->exactly(2))->will($this->returnValue('http://foo.com'));

        $client = Client::make();

        $this->assertInstanceOf(Client::class, $client);

        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $this->assertEquals(new Uri('http://foo.com'), $this->getGuzzle($client)->getConfig('base_uri'));
        } else {
            $this->assertSame('http://foo.com', $this->getGuzzle($client)->getBaseUrl());
        }
    }

    public function testDynamicConfigSetting()
    {
        $client = Client::make('foo');

        $this->assertTrue($client->isBatchSending());

        $this->assertSame($client, $client->setBatchSending(false));

        $this->assertFalse($client->isBatchSending());
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

        $this->guzzle->expects($this->never())->method('post');

        $this->client->notify(Report::fromNamedError($this->config, 'SkipMe', 'Message'));
    }

    public function testBeforeNotifyCanModifyReportFrames()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->setBatchSending(false);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Magic', 'oh no'));

        $this->assertFalse($report->getStacktrace()->getFrames()[0]["inProject"]);

        $this->client->registerCallback(function (Report $report) {
            $frames = &$report->getStacktrace()->getFrames();
            $frames[0]["inProject"] = true;

            return true;
        });

        $this->client->notify($report = Report::fromNamedError($this->config, 'Magic', 'oh no'));

        $this->assertTrue($report->getStacktrace()->getFrames()[0]["inProject"]);
    }

    public function testDirectCallbackSkipsError()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->setBatchSending(false);

        $this->guzzle->expects($this->never())->method('post');

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

    public function testBreadcrumbsLong()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->leaveBreadcrumb('This error name is far too long to be allowed through.', 'user', ['foo' => 'bar']);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(4, $breadcrumbs[0]);
        $this->assertInternalType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('This error name is far too lon', $breadcrumbs[0]['name']);
        $this->assertSame('user', $breadcrumbs[0]['type']);
        $this->assertSame(['foo' => 'bar'], $breadcrumbs[0]['metaData']);
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

    /**
     * @expectedException \BadMethodCallException
     */
    public function testBadMethodCall()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->foo();
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

        $this->guzzle->expects($this->once())->method('post');

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $this->client->flush();
        $this->client->flush();
        $this->client->flush();
    }

    public function testDeployWorksOutOfTheBox()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy();
    }

    public function testDeployWorksWithReleaseStage()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['releaseStage' => 'staging', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->config->setReleaseStage('staging');

        $this->client->deploy();
    }

    public function testDeployWorksWithAppVersion()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['releaseStage' => 'production', 'appVersion' => '1.1.0', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy();
    }

    public function testDeployWorksWithRepository()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['sourceControl' => ['repository' => 'foo'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy('foo');
    }

    public function testDeployWorksWithBranch()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy(null, 'master');
    }

    public function testDeployWorksWithRevision()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['sourceControl' => ['revision' => 'bar'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy(null, null, 'bar');
    }

    public function testDeployWorksWithEverything()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['sourceControl' => ['repository' => 'baz', 'revision' => 'foo'], 'releaseStage' => 'development', 'appVersion' => '1.3.1', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->config->setReleaseStage('development');
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy('baz', 'develop', 'foo');
    }

    public function testBuildWorksOutOfTheBox()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build();
    }

    public function testBuildWorksWithReleaseStage()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['releaseStage' => 'staging', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->config->setAppVersion('1.3.1');
        $this->config->setReleaseStage('staging');

        $this->client->build();
    }

    public function testBuildWorksWithRepository()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['sourceControl' => ['repository' => 'foo'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build('foo');
    }

    public function testBuildWorksWithProvider()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['sourceControl' => ['provider' => 'github'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, null, 'github');
    }

    public function testBuildWorksWithRevision()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['sourceControl' => ['revision' => 'bar'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, 'bar');
    }

    public function testBuildWorksWithBuilderName()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['builderName' => 'me', 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, null, null, 'me');
    }

    public function testBuildWorksWithBuildTool()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, null, null, null);
    }

    public function testBuildWorksWithEverything()
    {
        $this->guzzle->expects($this->once())->method('post')->with($this->equalTo('https://build.bugsnag.com'), $this->equalTo(['json' => ['builderName' => 'me', 'sourceControl' => ['repository' => 'baz', 'revision' => 'foo', 'provider' => 'github'], 'releaseStage' => 'development', 'appVersion' => '1.3.1', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php']]));

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
}
