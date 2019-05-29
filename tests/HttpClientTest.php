<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\Report;
use Exception;
use GuzzleHttp\Client;
use phpmock\phpunit\PHPMock;
use PHPUnit_Framework_TestCase as TestCase;

class HttpClientTest extends TestCase
{
    use PHPMock;

    protected $config;
    protected $guzzle;
    protected $http;

    protected function setUp()
    {
        $this->guzzle = $this->getMockBuilder(Client::class)
                             ->setMethods(['post'])
                             ->getMock();
        $this->config = new Configuration('6015a72ff14038114c3d12623dfb018f');
        $this->config->setGuzzleClient($this->guzzle);

        $this->http = new HttpClient($this->config);
    }

    public function testHttpClient()
    {
        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('post');

        // Add a report to the http and deliver it
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setMetaData(['foo' => 'bar']));
        $this->http->send();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = $invocations[0]->parameters;
        $this->assertCount(2, $params);
        $this->assertSame(\Bugsnag\Configuration::DEFAULT_NOTIFY_ENDPOINT, $params[0]);
        $this->assertInternalType('array', $params[1]);
        $this->assertInternalType('array', $params[1]['json']['notifier']);
        $this->assertInternalType('array', $params[1]['json']['events']);
        $this->assertSame([], $params[1]['json']['events'][0]['user']);
        $this->assertSame(['foo' => 'bar'], $params[1]['json']['events'][0]['metaData']);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $params[1]['json']['apiKey']);
        $this->assertSame('4.0', $params[1]['json']['events'][0]['payloadVersion']);

        $headers = $params[1]['headers'];
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $headers['Bugsnag-Api-Key']);
        $this->assertArrayHasKey('Bugsnag-Sent-At', $headers);
        $this->assertSame('4.0', $headers['Bugsnag-Payload-Version']);
    }

    public function testHttpClientUsesConfigSettings()
    {
        $config = $this->getMockBuilder(Configuration::class)
                       ->disableOriginalConstructor()
                       ->setMethods(['getNotifyEndpoint', 'getGuzzleClient'])
                       ->getMock();

        // Expect Client to get the guzzleClient and endpoint from Config
        $config->expects($this->once())->method('getGuzzleClient')->willReturn($this->guzzle);
        $config->expects($this->once())->method('getNotifyEndpoint')->willReturn('foo');

        // Expect post to be called
        $this->guzzle->expects($this->any())->method('post');

        $http = new HttpClient($config);
        $http->queue(Report::fromNamedError($this->config, 'Name'));
        $http->send();
    }

    public function testHttpClientMultipleSend()
    {
        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('post');

        // Add a report to the http and deliver it
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setMetaData(['foo' => 'bar']));
        $this->http->send();

        // Make sure these do nothing
        $this->http->send();
        $this->http->send();

        // Check we only sent once
        $this->assertCount(1, $invocations = $spy->getInvocations());
    }

    public function testMassiveMetaDataHttpClient()
    {
        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('post');

        // Add a report to the http and deliver it
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setMetaData(['foo' => str_repeat('A', 1000000)]));
        $this->http->send();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = $invocations[0]->parameters;
        $this->assertCount(2, $params);
        $this->assertSame(\Bugsnag\Configuration::DEFAULT_NOTIFY_ENDPOINT, $params[0]);
        $this->assertInternalType('array', $params[1]);
        $this->assertInternalType('array', $params[1]['json']['notifier']);
        $this->assertInternalType('array', $params[1]['json']['events']);
        $this->assertSame([], $params[1]['json']['events'][0]['user']);
        $this->assertArrayNotHasKey('metaData', $params[1]['json']['events'][0]);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $params[1]['json']['apiKey']);
        $this->assertSame('4.0', $params[1]['json']['events'][0]['payloadVersion']);

        $headers = $params[1]['headers'];
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $headers['Bugsnag-Api-Key']);
        $this->assertArrayHasKey('Bugsnag-Sent-At', $headers);
        $this->assertSame('4.0', $headers['Bugsnag-Payload-Version']);
    }

    public function testMassiveUserHttpClient()
    {
        // Setup error_log mocking
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Payload too large'));

        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('post');

        // Add a report to the http and deliver it
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setUser(['foo' => str_repeat('A', 1000000)]));
        $this->http->send();

        $this->assertCount(0, $spy->getInvocations());
    }

    public function testPartialHttpClient()
    {
        // Setup error_log mocking
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Payload too large'));

        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('post');

        // Add two errors to the http and deliver them
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setUser(['foo' => str_repeat('A', 1000000)]));
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setUser(['foo' => 'bar']));
        $this->http->send();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = $invocations[0]->parameters;
        $this->assertCount(2, $params);
        $this->assertSame(\Bugsnag\Configuration::DEFAULT_NOTIFY_ENDPOINT, $params[0]);
        $this->assertInternalType('array', $params[1]);
        $this->assertInternalType('array', $params[1]['json']['notifier']);
        $this->assertInternalType('array', $params[1]['json']['events']);
        $this->assertSame(['foo' => 'bar'], $params[1]['json']['events'][0]['user']);
        $this->assertSame([], $params[1]['json']['events'][0]['metaData']);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $params[1]['json']['apiKey']);
        $this->assertSame('4.0', $params[1]['json']['events'][0]['payloadVersion']);

        $headers = $params[1]['headers'];
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $headers['Bugsnag-Api-Key']);
        $this->assertArrayHasKey('Bugsnag-Sent-At', $headers);
        $this->assertSame('4.0', $headers['Bugsnag-Payload-Version']);
    }

    public function testHttpClientFails()
    {
        // Setup error_log mocking
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Couldn\'t notify. Guzzle exception thrown!'));

        // Expect request to be called
        $this->guzzle->method('post')->will($this->throwException(new Exception('Guzzle exception thrown!')));

        // Add a report to the http and deliver it
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setMetaData(['foo' => 'bar']));
        $this->http->send();
    }
}
