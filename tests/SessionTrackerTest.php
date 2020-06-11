<?php

namespace Bugsnag\Tests\SessionTracker;

use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\SessionTracker\SessionTracker;
use Bugsnag\SessionTracker\SessionTrackerInterface;
use Bugsnag\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;

class SessionTrackerTest extends TestCase
{
    /**
     * @var SessionTracker
     */
    private $sessionTracker;

    /**
     * @var Configuration&MockObject
     */
    private $config;

    /**
     * @var HttpClient&MockObject
     */
    private $client;

    public function setUp()
    {
        /** @var Configuration&MockObject */
        $this->config = $this->getMockBuilder(Configuration::class)
            ->setConstructorArgs(['example-api-key'])
            ->getMock();

        /** @var HttpClient&MockObject */
        $this->client = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionTracker = new SessionTracker($this->config, $this->client);
    }

    public function testItImplementsTheSessionTrackerInterface()
    {
        $this->assertInstanceOf(
            SessionTrackerInterface::class,
            $this->sessionTracker
        );
    }

    public function testSendSessionsDoesNothingIfThereIsNoDataToSend()
    {
        $this->client->expects($this->never())->method('sendSessions');

        $this->sessionTracker->sendSessions();
    }

    public function testSendSessionsDoesNothingIfReleaseStageIsIgnored()
    {
        $this->config->expects($this->once())->method('shouldNotify')->willReturn(false);
        $this->client->expects($this->never())->method('sendSessions');

        $this->sessionTracker->startSession();
        $this->sessionTracker->sendSessions();
    }

    public function testSessionsCanBeSentExplicitly()
    {
        $this->config->expects($this->once())->method('shouldNotify')->willReturn(true);
        $this->config->expects($this->once())->method('getNotifier')->willReturn('test_notifier');
        $this->config->expects($this->once())->method('getDeviceData')->willReturn('device_data');
        $this->config->expects($this->once())->method('getAppData')->willReturn('app_data');

        $expectCallback = function ($payload) {
            return count($payload) == 4
                && $payload['notifier'] === 'test_notifier'
                && $payload['device'] === 'device_data'
                && $payload['app'] === 'app_data'
                && count($payload['sessionCounts']) === 1
                && preg_match(
                    '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',
                    $payload['sessionCounts'][0]['startedAt']
                )
                && $payload['sessionCounts'][0]['sessionsStarted'] === 1;
        };

        $this->client->expects($this->once())
            ->method('sendSessions')
            ->with($this->callback($expectCallback));

        // Set the lastSent property of the SessionTracker so that 'startSession'
        // doesn't immediately send sessions
        $setLastSent = function () {
            $this->lastSent = time();
        };

        $setLastSent = $setLastSent->bindTo($this->sessionTracker, $this->sessionTracker);

        $setLastSent();

        $this->sessionTracker->startSession();
        $this->sessionTracker->sendSessions();
    }

    public function testSessionsAreSentOnStartSessionIfNotRecentlySent()
    {
        $this->config->expects($this->once())->method('shouldNotify')->willReturn(true);
        $this->config->expects($this->once())->method('getNotifier')->willReturn('test_notifier');
        $this->config->expects($this->once())->method('getDeviceData')->willReturn('device_data');
        $this->config->expects($this->once())->method('getAppData')->willReturn('app_data');

        $expectCallback = function ($payload) {
            return count($payload) == 4
                && $payload['notifier'] === 'test_notifier'
                && $payload['device'] === 'device_data'
                && $payload['app'] === 'app_data'
                && count($payload['sessionCounts']) === 1
                && preg_match(
                    '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',
                    $payload['sessionCounts'][0]['startedAt']
                )
                && $payload['sessionCounts'][0]['sessionsStarted'] === 1;
        };

        $this->client->expects($this->once())
            ->method('sendSessions')
            ->with($this->callback($expectCallback));

        $this->sessionTracker->startSession();
    }

    public function testSetRetryFunctionThrowsWhenNotGivenACallable()
    {
        $this->expectedException(InvalidArgumentException::class, 'The retry function must be callable');

        $this->sessionTracker->setRetryFunction(null);
    }
}
