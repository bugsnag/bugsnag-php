<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\HttpClient;
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
        $this->config = new Configuration('6015a72ff14038114c3d12623dfb018f');

        $this->guzzle = $this->getMockBuilder(Client::class)
                             ->setMethods(['request'])
                             ->getMock();

        $this->http = new HttpClient($this->config, $this->guzzle);
    }

    public function testHttpClient()
    {
        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('request');

        // Add an error to the http and deliver it
        $this->http->queue(Error::fromNamedError($this->config, 'Name')->setMetaData(['foo' => 'bar']));
        $this->http->send();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = $invocations[0]->parameters;
        $this->assertCount(3, $params);
        $this->assertSame('POST', $params[0]);
        $this->assertSame('/', $params[1]);
        $this->assertInternalType('array', $params[2]);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $params[2]['json']['apiKey']);
        $this->assertInternalType('array', $params[2]['json']['notifier']);
        $this->assertInternalType('array', $params[2]['json']['events']);
        $this->assertSame([], $params[2]['json']['events'][0]['user']);
        $this->assertSame(['foo' => 'bar'], $params[2]['json']['events'][0]['metaData']);
    }

    public function testHttpClientMultipleSend()
    {
        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('request');

        // Add an error to the http and deliver it
        $this->http->queue(Error::fromNamedError($this->config, 'Name')->setMetaData(['foo' => 'bar']));
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
        $this->guzzle->expects($spy = $this->any())->method('request');

        // Add an error to the http and deliver it
        $this->http->queue(Error::fromNamedError($this->config, 'Name')->setMetaData(['foo' => str_repeat('A', 1000000)]));
        $this->http->send();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = $invocations[0]->parameters;
        $this->assertCount(3, $params);
        $this->assertSame('POST', $params[0]);
        $this->assertSame('/', $params[1]);
        $this->assertInternalType('array', $params[2]);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $params[2]['json']['apiKey']);
        $this->assertInternalType('array', $params[2]['json']['notifier']);
        $this->assertInternalType('array', $params[2]['json']['events']);
        $this->assertSame([], $params[2]['json']['events'][0]['user']);
        $this->assertArrayNotHasKey('metaData', $params[2]['json']['events'][0]);
    }

    public function testMassiveUserHttpClient()
    {
        // Setup error_log mocking
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Payload too large'));

        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('request');

        // Add an error to the http and deliver it
        $this->http->queue(Error::fromNamedError($this->config, 'Name')->setUser(['foo' => str_repeat('A', 1000000)]));
        $this->http->send();

        $this->assertCount(0, $spy->getInvocations());
    }

    public function testPartialHttpClient()
    {
        // Setup error_log mocking
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Payload too large'));

        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('request');

        // Add two errors to the http and deliver them
        $this->http->queue(Error::fromNamedError($this->config, 'Name')->setUser(['foo' => str_repeat('A', 1000000)]));
        $this->http->queue(Error::fromNamedError($this->config, 'Name')->setUser(['foo' => 'bar']));
        $this->http->send();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = $invocations[0]->parameters;
        $this->assertCount(3, $params);
        $this->assertSame('POST', $params[0]);
        $this->assertSame('/', $params[1]);
        $this->assertInternalType('array', $params[2]);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $params[2]['json']['apiKey']);
        $this->assertInternalType('array', $params[2]['json']['notifier']);
        $this->assertInternalType('array', $params[2]['json']['events']);
        $this->assertSame(['foo' => 'bar'], $params[2]['json']['events'][0]['user']);
        $this->assertSame([], $params[2]['json']['events'][0]['metaData']);
    }

    public function testHttpClientFails()
    {
        // Setup error_log mocking
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Couldn\'t notify. Guzzle exception thrown!'));

        // Expect request to be called
        $this->guzzle->method('request')->will($this->throwException(new Exception('Guzzle exception thrown!')));

        // Add an error to the http and deliver it
        $this->http->queue(Error::fromNamedError($this->config, 'Name')->setMetaData(['foo' => 'bar']));
        $this->http->send();
    }
}
