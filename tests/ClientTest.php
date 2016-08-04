<?php

namespace Bugsnag\Tests;

use Bugsnag\Client;
use Bugsnag\Configuration;
use Bugsnag\Report;
use Exception;
use GuzzleHttp\Client as Guzzle;
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
                             ->setMethods(['request'])
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
        $this->client->expects($this->once())->method('notify');

        $this->client->notifyError('SomeError', 'Some message', function ($report) {
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
        $this->assertEquals(new Uri('https://notify.bugsnag.com'), $this->getGuzzle(Client::make('123'))->getConfig('base_uri'));
    }

    public function testCanMake()
    {
        $client = Client::make('123', 'https://example.com');

        $this->assertInstanceOf(Client::class, $client);

        $this->assertEquals(new Uri('https://example.com'), $this->getGuzzle($client)->getConfig('base_uri'));
    }

    public function testCanMakeFromEnv()
    {
        $env = $this->getFunctionMock('Bugsnag', 'getenv');
        $env->expects($this->exactly(2))->will($this->returnValue('http://foo.com'));

        $client = Client::make();

        $this->assertInstanceOf(Client::class, $client);

        $this->assertEquals(new Uri('http://foo.com'), $this->getGuzzle($client)->getConfig('base_uri'));
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

        $this->client->registerCallback(function (Report $report) {
            if ($report->getName() === 'SkipMe') {
                return false;
            }
        });

        $this->guzzle->expects($this->never())->method('request');

        $this->client->notify(Report::fromNamedError($this->config, 'SkipMe', 'Message'));
    }

    public function testMetaDataWorks()
    {
        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'), function ($report) {
            $report->setMetaData(['foo' => 'baz']);
        });

        $this->assertSame(['foo' => 'baz'], $report->getMetaData());
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

        $this->guzzle->expects($this->once())->method('request');

        $this->client->notify($report = Report::fromNamedError($this->config, 'Name'));

        $this->client->flush();
        $this->client->flush();
        $this->client->flush();
    }

    public function testDeployWorksOutOfTheBox()
    {
        $this->guzzle->expects($this->once())->method('request')->with($this->equalTo('POST'), $this->equalTo('deploy'), $this->equalTo(['json' => ['releaseStage' => 'production', 'apiKey' => 'example-api-key']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->deploy();
    }

    public function testDeployWorksWithgReleaseStage()
    {
        $this->guzzle->expects($this->once())->method('request')->with($this->equalTo('POST'), $this->equalTo('deploy'), $this->equalTo(['json' => ['releaseStage' => 'staging', 'apiKey' => 'example-api-key']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->config->setReleaseStage('staging');

        $this->client->deploy();
    }

    public function testDeployWorksWithAppVersion()
    {
        $this->guzzle->expects($this->once())->method('request')->with($this->equalTo('POST'), $this->equalTo('deploy'), $this->equalTo(['json' => ['releaseStage' => 'production', 'appVersion' => '1.1.0', 'apiKey' => 'example-api-key']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->config->setAppVersion('1.1.0');

        $this->client->deploy();
    }

    public function testDeployWorksWithRepository()
    {
        $this->guzzle->expects($this->once())->method('request')->with($this->equalTo('POST'), $this->equalTo('deploy'), $this->equalTo(['json' => ['repository' => 'foo', 'releaseStage' => 'production', 'apiKey' => 'example-api-key']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->deploy('foo');
    }

    public function testDeployWorksWithBranch()
    {
        $this->guzzle->expects($this->once())->method('request')->with($this->equalTo('POST'), $this->equalTo('deploy'), $this->equalTo(['json' => ['branch' => 'master', 'releaseStage' => 'production', 'apiKey' => 'example-api-key']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->deploy(null, 'master');
    }

    public function testDeployWorksWithRevision()
    {
        $this->guzzle->expects($this->once())->method('request')->with($this->equalTo('POST'), $this->equalTo('deploy'), $this->equalTo(['json' => ['revision' => 'bar', 'releaseStage' => 'production', 'apiKey' => 'example-api-key']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->client->deploy(null, null, 'bar');
    }

    public function testDeployWorksWithEverything()
    {
        $this->guzzle->expects($this->once())->method('request')->with($this->equalTo('POST'), $this->equalTo('deploy'), $this->equalTo(['json' => ['repository' => 'baz', 'branch' => 'develop', 'revision' => 'foo', 'releaseStage' => 'development', 'appVersion' => '1.3.1', 'apiKey' => 'example-api-key']]));

        $this->client = new Client($this->config = new Configuration('example-api-key'), null, $this->guzzle);

        $this->config->setReleaseStage('development');
        $this->config->setAppVersion('1.3.1');

        $this->client->deploy('baz', 'develop', 'foo');
    }
}
