<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\Report;
use Exception;
use GuzzleHttp\Client;

class HttpClientTest extends TestCase
{
    protected $config;
    protected $guzzle;
    protected $http;

    /**
     * @before
     */
    protected function beforeEach()
    {
        $this->config = new Configuration('6015a72ff14038114c3d12623dfb018f');

        $this->guzzle = $this->getMockBuilder(Client::class)
                             ->setMethods([self::getGuzzleMethod()])
                             ->getMock();

        $this->http = new HttpClient($this->config, $this->guzzle);
    }

    private static function getInvocationParameters($invocation)
    {
        if (is_callable([$invocation, 'getParameters'])) {
            return $invocation->getParameters();
        }

        return $invocation->parameters;
    }

    public function testHttpClient()
    {
        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method(self::getGuzzleMethod());

        // Add a report to the http and deliver it
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setMetaData(['foo' => 'bar']));
        $this->http->send();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = self::getInvocationParameters($invocations[0]);
        $this->assertCount(self::getGuzzleExpectedParamCount(), $params);
        $this->assertSame($this->config->getNotifyEndpoint(), self::getGuzzlePostUriParam($params));
        $options = self::getGuzzlePostOptionsParam($params);
        Assert::isType('array', $options);
        Assert::isType('array', $options['json']['notifier']);
        Assert::isType('array', $options['json']['events']);
        $this->assertSame([], $options['json']['events'][0]['user']);
        $this->assertSame(['foo' => 'bar'], $options['json']['events'][0]['metaData']);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $options['json']['apiKey']);
        $this->assertSame('4.0', $options['json']['events'][0]['payloadVersion']);

        $headers = $options['headers'];
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $headers['Bugsnag-Api-Key']);
        $this->assertArrayHasKey('Bugsnag-Sent-At', $headers);
        $this->assertSame('4.0', $headers['Bugsnag-Payload-Version']);
    }

    public function testHttpClientMultipleSend()
    {
        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method(self::getGuzzleMethod());

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
        $this->guzzle->expects($spy = $this->any())->method(self::getGuzzleMethod());

        // Add a report to the http and deliver it
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setMetaData(['foo' => str_repeat('A', 1500000)]));
        $this->http->send();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = self::getInvocationParameters($invocations[0]);
        $this->assertCount(self::getGuzzleExpectedParamCount(), $params);
        $this->assertSame($this->config->getNotifyEndpoint(), self::getGuzzlePostUriParam($params));
        $options = self::getGuzzlePostOptionsParam($params);
        Assert::isType('array', $options);
        Assert::isType('array', $options['json']['notifier']);
        Assert::isType('array', $options['json']['events']);
        $this->assertSame([], $options['json']['events'][0]['user']);
        $this->assertArrayNotHasKey('metaData', $options['json']['events'][0]);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $options['json']['apiKey']);
        $this->assertSame('4.0', $options['json']['events'][0]['payloadVersion']);

        $headers = $options['headers'];
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
        $this->guzzle->expects($spy = $this->any())->method(self::getGuzzleMethod());

        // Add a report to the http and deliver it
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setUser(['foo' => str_repeat('A', 1500000)]));
        $this->http->send();

        $this->assertCount(0, $spy->getInvocations());
    }

    public function testPartialHttpClient()
    {
        // Setup error_log mocking
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Payload too large'));

        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method(self::getGuzzleMethod());

        // Add two errors to the http and deliver them
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setUser(['foo' => str_repeat('A', 1500000)]));
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setUser(['foo' => 'bar']));
        $this->http->send();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = self::getInvocationParameters($invocations[0]);
        $this->assertCount(self::getGuzzleExpectedParamCount(), $params);
        $this->assertSame($this->config->getNotifyEndpoint(), self::getGuzzlePostUriParam($params));
        $options = self::getGuzzlePostOptionsParam($params);
        Assert::isType('array', $options);
        Assert::isType('array', $options['json']['notifier']);
        Assert::isType('array', $options['json']['events']);
        $this->assertSame(['foo' => 'bar'], $options['json']['events'][0]['user']);
        $this->assertSame([], $options['json']['events'][0]['metaData']);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $options['json']['apiKey']);
        $this->assertSame('4.0', $options['json']['events'][0]['payloadVersion']);

        $headers = $options['headers'];
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
        $this->guzzle->method(self::getGuzzleMethod())->will($this->throwException(new Exception('Guzzle exception thrown!')));

        // Add a report to the http and deliver it
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setMetaData(['foo' => 'bar']));
        $this->http->send();
    }

    private function getGuzzleExpectedParamCount()
    {
        return self::getGuzzleMethod() === 'request' ? 3 : 2;
    }

    private function getGuzzlePostUriParam(array $params)
    {
        return $params[self::getGuzzleMethod() === 'request' ? 1 : 0];
    }

    private function getGuzzlePostOptionsParam(array $params)
    {
        return $params[self::getGuzzleMethod() === 'request' ? 2 : 1];
    }
}
