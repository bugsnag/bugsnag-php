<?php

namespace Bugsnag\Tests;

use Bugsnag\Client;
use Bugsnag\Configuration;
use Bugsnag\Internal\GuzzleCompat;
use Bugsnag\Report;
use Bugsnag\Tests\Fakes\FakeShutdownStrategy;
use Exception;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\Uri;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @backupGlobals enabled
 */
class ClientTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var MockObject&Guzzle
     */
    private $guzzle;

    /**
     * @var Client
     */
    private $client;

    /**
     * @before
     */
    protected function beforeEach()
    {
        $this->config = new Configuration('example-api-key');

        /** @var MockObject&Guzzle */
        $this->guzzle = $this->getMockBuilder(Guzzle::class)
            ->setMethods([self::getGuzzleMethod()])
            ->getMock();

        $this->client = new Client($this->config, null, $this->guzzle);
    }

    /**
     * @after
     */
    protected function afterEach()
    {
        putenv('BUGSNAG_API_KEY');
        putenv('BUGSNAG_ENDPOINT');
    }

    public function testManualErrorNotification()
    {
        $this->guzzle->expects($this->once())->method(self::getGuzzleMethod());

        $this->client->notifyError('SomeError', 'Some message');
        $this->client->flush();
    }

    public function testManualErrorNotificationWithSeverity()
    {
        $this->expectGuzzlePostWithCallback(
            $this->client->getNotifyEndpoint(),
            function ($options) {
                $payload = $this->getPayloadFromGuzzleOptions($options);

                $this->assertTrue(isset($payload['events'][0]['severity']));
                $this->assertSame('info', $payload['events'][0]['severity']);

                return true;
            }
        );

        $this->client->notifyError('SomeError', 'Some message', function ($report) {
            $report->setSeverity('info');
        });

        $this->client->flush();
    }

    public function testManualExceptionNotification()
    {
        $this->guzzle->expects($this->once())->method(self::getGuzzleMethod());

        $this->client->notifyException(new Exception('Something broke'));
        $this->client->flush();
    }

    public function testManualExceptionNotificationWithSeverity()
    {
        $this->expectGuzzlePostWithCallback(
            $this->client->getNotifyEndpoint(),
            function ($options) {
                $payload = $this->getPayloadFromGuzzleOptions($options);

                $this->assertTrue(isset($payload['events'][0]['severity']));
                $this->assertSame('info', $payload['events'][0]['severity']);

                return true;
            }
        );

        $this->client->notifyException(new Exception('Something broke'), function ($report) {
            $report->setSeverity('info');
        });

        $this->client->flush();
    }

    public function testTheNotifyEndpointHasASensibleDefault()
    {
        $client = Client::make('123');
        $expected = 'https://notify.bugsnag.com';

        $this->assertEquals($expected, $client->getNotifyEndpoint());
    }

    public function testTheNotifyEndpointCanBeSetByPassingItToMake()
    {
        $client = Client::make('123', 'https://example.com');

        $this->assertEquals('https://example.com', $client->getNotifyEndpoint());
    }

    public function testTheApiKeyAndNotifyEndpointCanBeSetViaEnvironmentVariables()
    {
        putenv('BUGSNAG_API_KEY=foobar');
        putenv('BUGSNAG_ENDPOINT=http://foo.com');

        $client = Client::make();

        $this->assertEquals('foobar', $client->getApiKey());
        $this->assertEquals('http://foo.com', $client->getNotifyEndpoint());
    }

    public function testTheApiKeyAndNotifyEndpointCanBeSetViaEnvSuperglobal()
    {
        $_ENV['BUGSNAG_API_KEY'] = 'baz';
        $_ENV['BUGSNAG_ENDPOINT'] = 'http://bar.com';

        $client = Client::make();

        $this->assertEquals('baz', $client->getApiKey());
        $this->assertEquals('http://bar.com', $client->getNotifyEndpoint());
    }

    public function testTheNotifyEndpointCanBeSetBySettingItOnAGuzzleInstance()
    {
        $guzzle = new Guzzle([
            GuzzleCompat::getBaseUriOptionName() => 'https://example.com',
        ]);

        $client = new Client(new Configuration('abc'), null, $guzzle);

        $this->assertEquals('https://example.com', $client->getNotifyEndpoint());
    }

    public function testTheNotifyEndpointWontBeOverwrittenWhenOneIsAlreadySetOnConfiguration()
    {
        $config = new Configuration('abc');
        $config->setNotifyEndpoint('https://foo.com');

        $client = new Client($config);

        $this->assertEquals('https://foo.com', $client->getNotifyEndpoint());
    }

    public function testTheNotifyEndpointWontBeOverwrittenByGivenGuzzleInstanceWhenOneIsAlreadySetOnConfiguration()
    {
        $config = new Configuration('abc');
        $config->setNotifyEndpoint('https://foo.com');

        $guzzle = new Guzzle([
            GuzzleCompat::getBaseUriOptionName() => 'https://example.com',
        ]);

        $client = new Client($config, null, $guzzle);

        $this->assertEquals('https://foo.com', $client->getNotifyEndpoint());
    }

    public function testTheNotifyEndpointWontBeOverwrittenByMakeGuzzleWhenOneIsAlreadySetOnConfiguration()
    {
        $config = new Configuration('abc');
        $config->setNotifyEndpoint('https://foo.com');

        $guzzle = Client::makeGuzzle();

        $client = new Client($config, null, $guzzle);

        $this->assertEquals('https://foo.com', $client->getNotifyEndpoint());
    }

    public function testTheNotifyEndpointCanBeSetBySettingItOnAGuzzleInstanceWithAnArray()
    {
        if (!GuzzleCompat::isUsingGuzzle5()) {
            $this->markTestSkipped(
                'This test is not relevant on Guzzle >= 6 as arrays are not allowed'
            );
        }

        $guzzle = new Guzzle([
            GuzzleCompat::getBaseUriOptionName() => [
                'https://example.com/{version}', ['version' => '1.2'],
            ],
        ]);

        $client = new Client(new Configuration('abc'), null, $guzzle);

        $this->assertEquals('https://example.com/1.2', $client->getNotifyEndpoint());
    }

    public function testTheNotifyEndpointCanBeSetBySettingItOnAGuzzleInstanceWithAUriInstance()
    {
        $guzzle = new Guzzle([
            GuzzleCompat::getBaseUriOptionName() => new Uri('https://example.com:8080/hello/world'),
        ]);

        $client = new Client(new Configuration('abc'), null, $guzzle);

        $this->assertEquals('https://example.com:8080/hello/world', $client->getNotifyEndpoint());
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

    public function testItAddsADefaultSetOfMiddlewares()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_COOKIE'] = 'tastes=delicious';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '8.76.54.321';
        $_SERVER['REQUEST_URI'] = '/abc/xyz?abc=1&xyz=2';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic YTpi';
        $_GET['abc'] = '1';
        $_GET['xyz'] = '2';
        $_COOKIE['tastes'] = 'delicious';
        $_SESSION['abcde'] = '12345';

        $client = Client::make('foo');
        $config = $client->getConfig();
        $report = Report::fromPHPThrowable(
            $config,
            new Exception('oh no')
        );

        $config->setMetaData(['abc' => 'xyz']);

        $pipelineCompleted = false;
        $pipeline = $client->getPipeline();

        $pipeline->execute(
            $report,
            function (Report $report) use (&$pipelineCompleted) {
                $pipelineCompleted = true;

                $expectedMetadata = [
                    'abc' => 'xyz',
                    'request' => [
                        'url' => 'http://example.com/abc/xyz?abc=1&xyz=2',
                        'httpMethod' => 'GET',
                        'params' => [
                            'abc' => '1',
                            'xyz' => '2',
                        ],
                        'clientIp' => '8.76.54.321',
                        'headers' => [
                            'Host' => 'example.com',
                            'Cookie' => 'tastes=delicious',
                            'X-Forwarded-For' => '8.76.54.321',
                            'Authorization' => 'Basic YTpi',
                        ],
                    ],
                    'session' => [
                        'abcde' => '12345',
                    ],
                ];

                $this->assertSame($expectedMetadata, $report->getMetaData());
                $this->assertSame(['id' => '8.76.54.321'], $report->getUser());
                $this->assertSame('GET /abc/xyz', $report->getContext());

                $payload = $report->toArray();

                $this->assertSame(
                    [
                        'Host' => 'example.com',
                        'Cookie' => '[FILTERED]',
                        'X-Forwarded-For' => '8.76.54.321',
                        'Authorization' => '[FILTERED]',
                    ],
                    $payload['metaData']['request']['headers']
                );
            }
        );

        $this->assertTrue($pipelineCompleted);
    }

    public function testItAddsDiscardClassesMiddlewareByDefault()
    {
        $syslog = $this->getFunctionMock('Bugsnag\Middleware', 'syslog');
        $syslog->expects($this->once())->with(
            LOG_INFO,
            'Discarding event because error class "Exception" matched discardClasses configuration'
        );

        $client = Client::make('foo');
        $client->setDiscardClasses([Exception::class]);

        $report = Report::fromPHPThrowable(
            $client->getConfig(),
            new Exception('oh no')
        );

        $pipeline = $client->getPipeline();
        $pipelineCompleted = false;

        $pipeline->execute(
            $report,
            function () use (&$pipelineCompleted) {
                $pipelineCompleted = true;

                throw new LogicException('This should never be reached!');
            }
        );

        $this->assertFalse($pipelineCompleted);
    }

    public function testBreadcrumbsWorks()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->leaveBreadcrumb('Test', 'user', ['foo' => 'bar']);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(4, $breadcrumbs[0]);
        Assert::isType('string', $breadcrumbs[0]['timestamp']);
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
        Assert::isType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Foo Bar Baz', $breadcrumbs[0]['name']);
        $this->assertSame('manual', $breadcrumbs[0]['type']);
        $this->assertFalse(isset($breadcrumbs[0]['metaData']));
    }

    public function testBreadcrumbsWithNoName()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        // notify with an empty report name
        $this->client->notify(Report::fromNamedError($this->config, ''));

        // then notify again to pickup the previous breadcrumb
        $this->client->notify($report = Report::fromNamedError($this->config, 'foo'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(4, $breadcrumbs[0]);
        Assert::isType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Error', $breadcrumbs[0]['name']);
        $this->assertSame('error', $breadcrumbs[0]['type']);
        $this->assertTrue(isset($breadcrumbs[0]['metaData']));
    }

    public function testBreadcrumbsWithClassName()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->leaveBreadcrumb(Client::class, 'state');

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(3, $breadcrumbs[0]);
        Assert::isType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Bugsnag\Client', $breadcrumbs[0]['name']);
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
        Assert::isType('string', $breadcrumbs[0]['timestamp']);
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
        Assert::isType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Test', $breadcrumbs[0]['name']);
        $this->assertSame('user', $breadcrumbs[0]['type']);
        $this->assertSame(['foo' => 'bar'], $breadcrumbs[0]['metaData']);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $breadcrumbs = $report->toArray()['breadcrumbs'];

        $this->assertCount(2, $breadcrumbs);

        $this->assertCount(4, $breadcrumbs[0]);
        Assert::isType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Test', $breadcrumbs[0]['name']);
        $this->assertSame('user', $breadcrumbs[0]['type']);
        $this->assertSame(['foo' => 'bar'], $breadcrumbs[0]['metaData']);

        $this->assertCount(4, $breadcrumbs[1]);
        Assert::isType('string', $breadcrumbs[1]['timestamp']);
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
        $this->guzzle->expects($this->never())->method(self::getGuzzleMethod());

        $this->client->notify(Report::fromNamedError($this->config, 'Name'));
    }

    public function testFlushesWhenNotBatching()
    {
        $this->guzzle->expects($this->once())->method(self::getGuzzleMethod());

        $this->client->setBatchSending(false);

        $this->client->notify(Report::fromNamedError($this->config, 'Name'));
    }

    public function testCanManuallyFlush()
    {
        $this->client->setBatchSending(false);

        $this->guzzle->expects($this->once())->method(self::getGuzzleMethod());

        $this->client->notify(Report::fromNamedError($this->config, 'Name'));

        $this->client->flush();
        $this->client->flush();
        $this->client->flush();
    }

    public function testDeployWorksOutOfTheBox()
    {
        $this->expectGuzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy();
    }

    public function testDeployWorksWithReleaseStage()
    {
        $this->expectGuzzlePostWith(
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
        $this->expectGuzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'production', 'appVersion' => '1.1.0', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy();
    }

    public function testDeployWorksWithRepository()
    {
        $this->expectGuzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['sourceControl' => ['repository' => 'foo'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy('foo');
    }

    public function testDeployWorksWithBranch()
    {
        $this->expectGuzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy(null, 'master');
    }

    public function testDeployWorksWithRevision()
    {
        $this->expectGuzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['sourceControl' => ['revision' => 'bar'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy(null, null, 'bar');
    }

    public function testDeployWorksWithEverything()
    {
        $this->expectGuzzlePostWith(
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
        $this->expectGuzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build();
    }

    public function testBuildWorksWithReleaseStage()
    {
        $this->expectGuzzlePostWith(
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
        $this->expectGuzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['sourceControl' => ['repository' => 'foo'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build('foo');
    }

    public function testBuildWorksWithProvider()
    {
        $this->expectGuzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['sourceControl' => ['provider' => 'github'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, null, 'github');
    }

    public function testBuildWorksWithRevision()
    {
        $this->expectGuzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['sourceControl' => ['revision' => 'bar'], 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, 'bar');
    }

    public function testBuildWorksWithBuilderName()
    {
        $this->expectGuzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['builderName' => 'me', 'releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, null, null, 'me');
    }

    public function testBuildWorksWithBuildTool()
    {
        $this->expectGuzzlePostWith(
            'https://build.bugsnag.com',
            ['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key', 'buildTool' => 'bugsnag-php', 'builderName' => exec('whoami'), 'appVersion' => '1.3.1']]
        );

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);
        $this->config->setAppVersion('1.3.1');

        $this->client->build(null, null, null, null);
    }

    public function testBuildWorksWithEverything()
    {
        $this->expectGuzzlePostWith(
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

    public function testSendArguments()
    {
        $client = Client::make('foo');
        $this->assertFalse($client->shouldSendArguments());
        $this->assertSame($client, $client->setSendArguments(true));
        $this->assertTrue($client->shouldSendArguments());
        $this->assertSame($client, $client->setSendArguments(false));
        $this->assertFalse($client->shouldSendArguments());
    }

    public function testBuildEndpoint()
    {
        $client = Client::make('foo');
        $this->assertSame($client, $client->setBuildEndpoint('https://example'));
        $this->assertSame('https://example', $client->getBuildEndpoint());
    }

    public function testTheSessionEndpointHasASensibleDefault()
    {
        $client = Client::make('foo');
        $expected = 'https://sessions.bugsnag.com';

        $this->assertSame($expected, $client->getSessionEndpoint());
    }

    public function testTheSessionEndpointCanBeSetIfNecessary()
    {
        $client = Client::make('foo');
        $expected = 'https://example.com';

        $client->setSessionEndpoint($expected);

        $this->assertSame($expected, $client->getSessionEndpoint());
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

        $this->assertSame($client, $client->setErrorReportingLevel(E_ALL & ~E_NOTICE));
        $this->assertTrue($client->shouldIgnoreErrorCode(E_NOTICE));

        $this->assertSame($client, $client->setErrorReportingLevel(E_NOTICE));
        $this->assertFalse($client->shouldIgnoreErrorCode(E_NOTICE));

        $this->assertSame($client, $client->setErrorReportingLevel(0));
        $this->assertTrue($client->shouldIgnoreErrorCode(E_NOTICE));

        $this->assertSame($client, $client->setErrorReportingLevel(null));
        $this->assertFalse($client->shouldIgnoreErrorCode(E_NOTICE));
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
        $shutdownStrategy = new FakeShutdownStrategy();

        $this->assertFalse($shutdownStrategy->wasRegistered());

        new Client($this->config, null, null, $shutdownStrategy);

        $this->assertTrue($shutdownStrategy->wasRegistered());
    }

    public function testMakeGuzzleCreatesAGuzzleInstanceWithATimeout()
    {
        $guzzle = Client::makeGuzzle();

        $timeout = $this->getGuzzleOption($guzzle, 'timeout');
        $connectTimeout = $this->getGuzzleOption($guzzle, 'connect_timeout');

        $this->assertSame(Client::DEFAULT_TIMEOUT_S, $timeout);
        $this->assertSame(Client::DEFAULT_TIMEOUT_S, $connectTimeout);
    }

    public function testMakeGuzzleCreatesTimeoutCanBeSpecified()
    {
        $options = ['timeout' => 1, 'connect_timeout' => 2];

        if (GuzzleCompat::isUsingGuzzle5()) {
            $options = ['defaults' => $options];
        }

        $guzzle = Client::makeGuzzle(null, $options);

        $timeout = $this->getGuzzleOption($guzzle, 'timeout');
        $connectTimeout = $this->getGuzzleOption($guzzle, 'connect_timeout');

        $this->assertSame(1, $timeout);
        $this->assertSame(2, $connectTimeout);
    }

    public function testMemoryLimitIncreaseDefault()
    {
        $this->assertSame(1024 * 1024 * 5, $this->client->getMemoryLimitIncrease());
    }

    public function testMemoryLimitIncreaseCanBeSet()
    {
        $this->client->setMemoryLimitIncrease(12345);

        $this->assertSame(12345, $this->client->getMemoryLimitIncrease());
    }

    public function testMemoryLimitIncreaseCanBeSetToNull()
    {
        $this->client->setMemoryLimitIncrease(null);

        $this->assertNull($this->client->getMemoryLimitIncrease());
    }

    public function testDiscardClassesDefault()
    {
        $this->assertSame([], $this->client->getDiscardClasses());
    }

    public function testDiscardClassesCanBeSet()
    {
        $discardClasses = [
            \RuntimeException::class,
            \LogicException::class,
            \TypeError::class,
            '/^(Under|Over)flowException$/',
        ];

        $this->client->setDiscardClasses($discardClasses);

        $this->assertSame($discardClasses, $this->client->getDiscardClasses());
    }

    public function testRedactedKeysDefault()
    {
        $this->assertSame([], $this->client->getRedactedKeys());
    }

    public function testRedactedKeysCanBeSet()
    {
        $redactedKeys = ['password', 'password_confirmation'];

        $this->client->setRedactedKeys($redactedKeys);

        $this->assertSame($redactedKeys, $this->client->getRedactedKeys());
    }

    private function getGuzzleOption($guzzle, $name)
    {
        if (GuzzleCompat::isUsingGuzzle5()) {
            return $guzzle->getDefaultOption($name);
        }

        return $guzzle->getConfig($name);
    }

    private function expectGuzzlePostWith($uri, array $options = [])
    {
        $method = self::getGuzzleMethod();
        $mock = $this->guzzle->expects($this->once())->method($method);

        if ($method === 'request') {
            return $mock->with('POST', $uri, $options);
        }

        return $mock->with($uri, $options);
    }

    private function expectGuzzlePostWithCallback($uri, $callback)
    {
        $method = self::getGuzzleMethod();
        $mock = $this->guzzle->expects($this->once())->method($method);

        if ($method === 'request') {
            return $mock->with('POST', $uri, $this->callback($callback));
        }

        return $mock->with($uri, $this->callback($callback));
    }
}
