<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\SessionTracker;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

class SessionTrackerTest extends TestCase
{
    /** @var SessionTracker */
    private $sessionTracker;
    /** @var Configuration&MockObject */
    private $config;
    /** @var HttpClient&MockObject */
    private $httpClient;
    /** @var ClientInterface&MockObject */
    private $guzzleClient;

    public function setUp()
    {
        /** @var Configuration&MockObject */
        $this->config = $this->getMockBuilder(Configuration::class)
            ->setConstructorArgs(['example-api-key'])
            ->getMock();

        $this->sessionTracker = new SessionTracker($this->config);

        $this->httpClient = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['post'])
            ->getMock();

        $this->guzzleClient = $this->getMockBuilder(ClientInterface::class)
            ->getMock();
    }

    public function testSendSessionsEmpty()
    {
        $this->config->expects($this->never())->method('getSessionClient');
        $this->httpClient->expects($this->never())->method('post');

        $this->sessionTracker->sendSessions();
    }

    public function testSendSessionsShouldNotNotify()
    {
        $numberOfCalls = 0;

        $this->sessionTracker->setStorageFunction(function ($key, $value = null) use (&$numberOfCalls) {
            $numberOfCalls++;

            if ($value === null) {
                $this->assertSame(1, $numberOfCalls, 'Expected the first call to be a read ($value === null)');

                return ['2000-01-01T00:00:00' => 1];
            }

            $this->assertSame(2, $numberOfCalls, 'Expected the second call to be a write ($value === [])');
            $this->assertSame([], $value, 'Expected the second call to be a write ($value === [])');
        });

        $this->config->expects($this->once())->method('shouldNotify')->willReturn(false);
        $this->config->expects($this->never())->method('getSessionClient');
        $this->httpClient->expects($this->never())->method('post');

        $this->sessionTracker->sendSessions();

        $this->assertSame(2, $numberOfCalls, 'Expected there to be two calls to the session storage function');
    }

    /**
     * @param mixed $returnValue
     *
     * @return void
     *
     * @dataProvider storageFunctionEmptyReturnValueProvider
     */
    public function testSendSessionsReturnsEarlyWhenGetSessionCountsReturnsAValueThatsNotAPopulatedArray($returnValue)
    {
        $this->sessionTracker->setStorageFunction(function () use ($returnValue) {
            return $returnValue;
        });

        $this->config->expects($this->never())->method('shouldNotify');
        $this->config->expects($this->never())->method('getSessionClient');
        $this->config->expects($this->never())->method('getNotifier');
        $this->config->expects($this->never())->method('getDeviceData');
        $this->config->expects($this->never())->method('getAppData');
        $this->config->expects($this->never())->method('getApiKey');
        $this->guzzleClient->expects($this->never())->method('request');

        $this->sessionTracker->sendSessions();
    }

    public function storageFunctionEmptyReturnValueProvider()
    {
        return [
            'null' => [null],
            'empty array' => [[]],
            'int' => [1],
            'float' => [1.2],
            'bool (true)' => [true],
            'bool (false)' => [false],
            'object' => [new stdClass()],
        ];
    }

    public function testSendSessionsSuccessWhenCallingSendSessions()
    {
        $this->sessionTracker->setStorageFunction(function ($key, $value = null) {
            return ['2000-01-01T00:00:00' => 1];
        });

        $this->config->expects($this->once())->method('shouldNotify')->willReturn(true);
        $this->config->expects($this->once())->method('getSessionClient')->willReturn($this->guzzleClient);
        $this->config->expects($this->once())->method('getNotifier')->willReturn('test_notifier');
        $this->config->expects($this->once())->method('getDeviceData')->willReturn('device_data');
        $this->config->expects($this->once())->method('getAppData')->willReturn('app_data');
        $this->config->expects($this->once())->method('getApiKey')->willReturn('example-api-key');

        $this->guzzleClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo(''),
                $this->callback(function ($sessionPayload) {
                    return count($sessionPayload) == 2
                        && $sessionPayload['json']['notifier'] === 'test_notifier'
                        && $sessionPayload['json']['device'] === 'device_data'
                        && $sessionPayload['json']['app'] === 'app_data'
                        && count($sessionPayload['json']['sessionCounts']) === 1
                        && $sessionPayload['json']['sessionCounts'][0]['startedAt'] === '2000-01-01T00:00:00'
                        && $sessionPayload['json']['sessionCounts'][0]['sessionsStarted'] === 1
                        && $sessionPayload['headers']['Bugsnag-Api-Key'] == 'example-api-key'
                        && preg_match('/(\d+\.)+/', $sessionPayload['headers']['Bugsnag-Payload-Version'])
                        && preg_match('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $sessionPayload['headers']['Bugsnag-Sent-At']);
                })
            );

        $this->sessionTracker->sendSessions();
    }

    public function testSendSessionsSuccessWhenCallingStartSession()
    {
        $session = [];

        $this->sessionTracker->setStorageFunction(function ($key, $value = null) use (&$session) {
            if (!isset($session[$key])) {
                $session[$key] = null;
            }

            if ($value === null) {
                return $session[$key];
            }

            $session[$key] = $value;
        });

        $this->config->expects($this->once())->method('shouldNotify')->willReturn(true);
        $this->config->expects($this->once())->method('getSessionClient')->willReturn($this->guzzleClient);
        $this->config->expects($this->once())->method('getNotifier')->willReturn('test_notifier');
        $this->config->expects($this->once())->method('getDeviceData')->willReturn('device_data');
        $this->config->expects($this->once())->method('getAppData')->willReturn('app_data');
        $this->config->expects($this->once())->method('getApiKey')->willReturn('example-api-key');

        $this->guzzleClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo(''),
                $this->callback(function ($sessionPayload) {
                    return count($sessionPayload) == 2
                        && $sessionPayload['json']['notifier'] === 'test_notifier'
                        && $sessionPayload['json']['device'] === 'device_data'
                        && $sessionPayload['json']['app'] === 'app_data'
                        && count($sessionPayload['json']['sessionCounts']) === 1
                        && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $sessionPayload['json']['sessionCounts'][0]['startedAt'])
                        && $sessionPayload['json']['sessionCounts'][0]['sessionsStarted'] === 1
                        && $sessionPayload['headers']['Bugsnag-Api-Key'] == 'example-api-key'
                        && preg_match('/(\d+\.)+/', $sessionPayload['headers']['Bugsnag-Payload-Version'])
                        && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $sessionPayload['headers']['Bugsnag-Sent-At']);
                })
            );

        $this->sessionTracker->startSession();
    }
}
