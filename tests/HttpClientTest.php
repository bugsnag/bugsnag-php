<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\Internal\GuzzleCompat;
use Bugsnag\Report;
use Exception;
use GuzzleHttp\Client;
use PHPUnit\Framework\MockObject\MockObject;

class HttpClientTest extends TestCase
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var MockObject&Client
     */
    protected $guzzle;

    /**
     * @var HttpClient
     */
    protected $http;

    /**
     * @before
     */
    protected function beforeEach()
    {
        $this->config = new Configuration('6015a72ff14038114c3d12623dfb018f');

        /** @var MockObject&Client */
        $this->guzzle = $this->getMockBuilder(Client::class)->getMock();

        $this->http = new HttpClient($this->config, $this->guzzle);
    }

    private function setExpectedGuzzleParameters($expectation, $callback)
    {
        if (GuzzleCompat::isUsingGuzzle5()) {
            $expectation->with(
                $this->config->getNotifyEndpoint(),
                $this->callback($callback)
            );
        } else {
            $expectation->with(
                'POST',
                $this->config->getNotifyEndpoint(),
                $this->callback($callback)
            );
        }
    }

    public function testHttpClient()
    {
        $verifyGuzzleParameters = function ($options) {
            $payload = $this->getPayloadFromGuzzleOptions($options);

            Assert::isType('array', $payload);
            Assert::isType('array', $payload['notifier']);
            Assert::isType('array', $payload['events']);
            $this->assertSame([], $payload['events'][0]['user']);
            $this->assertSame(['foo' => 'bar'], $payload['events'][0]['metaData']);
            $this->assertSame('6015a72ff14038114c3d12623dfb018f', $payload['apiKey']);
            $this->assertSame('4.0', $payload['events'][0]['payloadVersion']);

            $headers = $options['headers'];

            $this->assertSame('6015a72ff14038114c3d12623dfb018f', $headers['Bugsnag-Api-Key']);
            Assert::matchesBugsnagDateFormat($headers['Bugsnag-Sent-At']);
            $this->assertSame('4.0', $headers['Bugsnag-Payload-Version']);

            return true;
        };

        $expectation = $this->guzzle->expects($this->once())
            ->method(self::getGuzzleMethod());

        $this->setExpectedGuzzleParameters($expectation, $verifyGuzzleParameters);

        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setMetaData(['foo' => 'bar']));
        $this->http->send();
    }

    public function testHttpClientMultipleSend()
    {
        $verifyGuzzleParameters = function ($options) {
            $payload = $this->getPayloadFromGuzzleOptions($options);

            Assert::isType('array', $payload);
            Assert::isType('array', $payload['notifier']);
            Assert::isType('array', $payload['events']);
            $this->assertSame([], $payload['events'][0]['user']);
            $this->assertSame(['foo' => 'bar'], $payload['events'][0]['metaData']);
            $this->assertSame('6015a72ff14038114c3d12623dfb018f', $payload['apiKey']);
            $this->assertSame('4.0', $payload['events'][0]['payloadVersion']);

            $headers = $options['headers'];

            $this->assertSame('6015a72ff14038114c3d12623dfb018f', $headers['Bugsnag-Api-Key']);
            Assert::matchesBugsnagDateFormat($headers['Bugsnag-Sent-At']);
            $this->assertSame('4.0', $headers['Bugsnag-Payload-Version']);

            return true;
        };

        $expectation = $this->guzzle->expects($this->once())
            ->method(self::getGuzzleMethod());

        $this->setExpectedGuzzleParameters($expectation, $verifyGuzzleParameters);

        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setMetaData(['foo' => 'bar']));
        $this->http->send();
        $this->http->send();
    }

    public function testMassiveMetaDataHttpClient()
    {
        $verifyGuzzleParameters = function ($options) {
            $payload = $this->getPayloadFromGuzzleOptions($options);

            Assert::isType('array', $payload);
            Assert::isType('array', $payload['notifier']);
            Assert::isType('array', $payload['events']);
            $this->assertSame([], $payload['events'][0]['user']);
            $this->assertArrayNotHasKey('metaData', $payload['events'][0]);
            $this->assertSame('6015a72ff14038114c3d12623dfb018f', $payload['apiKey']);
            $this->assertSame('4.0', $payload['events'][0]['payloadVersion']);

            $headers = $options['headers'];

            $this->assertSame('6015a72ff14038114c3d12623dfb018f', $headers['Bugsnag-Api-Key']);
            Assert::matchesBugsnagDateFormat($headers['Bugsnag-Sent-At']);
            $this->assertSame('4.0', $headers['Bugsnag-Payload-Version']);

            return true;
        };

        $expectation = $this->guzzle->expects($this->once())
            ->method(self::getGuzzleMethod());

        $this->setExpectedGuzzleParameters($expectation, $verifyGuzzleParameters);

        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setMetaData(['foo' => str_repeat('A', 1500000)]));
        $this->http->send();
    }

    public function testMassiveUserHttpClient()
    {
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())
            ->with($this->equalTo('Bugsnag Warning: Payload too large'));

        $this->guzzle->expects($this->never())
            ->method(self::getGuzzleMethod())
            ->withAnyParameters();

        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setUser(['foo' => str_repeat('A', 1500000)]));
        $this->http->send();
    }

    public function testPartialHttpClient()
    {
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Payload too large'));

        $verifyGuzzleParameters = function ($options) {
            $payload = $this->getPayloadFromGuzzleOptions($options);

            Assert::isType('array', $payload);
            Assert::isType('array', $payload['notifier']);
            Assert::isType('array', $payload['events']);
            $this->assertSame(['foo' => 'bar'], $payload['events'][0]['user']);
            $this->assertSame([], $payload['events'][0]['metaData']);
            $this->assertSame('6015a72ff14038114c3d12623dfb018f', $payload['apiKey']);
            $this->assertSame('4.0', $payload['events'][0]['payloadVersion']);

            $headers = $options['headers'];
            $this->assertSame('6015a72ff14038114c3d12623dfb018f', $headers['Bugsnag-Api-Key']);
            Assert::matchesBugsnagDateFormat($headers['Bugsnag-Sent-At']);
            $this->assertSame('4.0', $headers['Bugsnag-Payload-Version']);

            return true;
        };

        $expectation = $this->guzzle->expects($this->once())
            ->method(self::getGuzzleMethod());

        $this->setExpectedGuzzleParameters($expectation, $verifyGuzzleParameters);

        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setUser(['foo' => str_repeat('A', 1500000)]));
        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setUser(['foo' => 'bar']));
        $this->http->send();
    }

    public function testHttpClientFails()
    {
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())
            ->with($this->equalTo(
                "Bugsnag Warning: Couldn't notify. Guzzle exception thrown!"
            ));

        $this->guzzle->expects($this->once())
            ->method(self::getGuzzleMethod())
            ->will($this->throwException(new Exception('Guzzle exception thrown!')));

        $this->http->queue(Report::fromNamedError($this->config, 'Name')->setMetaData(['foo' => 'bar']));
        $this->http->send();
    }
}
