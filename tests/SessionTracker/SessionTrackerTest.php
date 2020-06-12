<?php

namespace Bugsnag\Tests\SessionTracker;

use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\SessionTracker\CurrentSession;
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
     * @var Configuration
     */
    private $config;

    /**
     * @var HttpClient&MockObject
     */
    private $client;

    public function setUp()
    {
        $this->config = new Configuration('example-api-key');

        /** @var HttpClient&MockObject */
        $this->client = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionTracker = new SessionTracker(
            $this->config,
            $this->client,
            new CurrentSession()
        );
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
        $this->config->setReleaseStage('development');
        $this->config->setNotifyReleaseStages(['production']);

        $this->client->expects($this->never())->method('sendSessions');

        $this->sessionTracker->startSession();
        $this->sessionTracker->sendSessions();
    }

    public function testSessionsCanBeSentExplicitly()
    {
        $expectCallback = function ($payload) {
            $this->assertArrayHasKey('notifier', $payload);
            $this->assertArrayHasKey('device', $payload);
            $this->assertArrayHasKey('app', $payload);
            $this->assertArrayHasKey('sessionCounts', $payload);

            $this->assertSame($this->config->getNotifier(), $payload['notifier']);
            $this->assertSame($this->config->getDeviceData(), $payload['device']);
            $this->assertSame($this->config->getAppData(), $payload['app']);

            $this->assertCount(1, $payload['sessionCounts']);

            $session = $payload['sessionCounts'][0];

            $this->assertArrayHasKey('startedAt', $session);
            $this->assertArrayHasKey('sessionsStarted', $session);

            $this->assertRegExp('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $session['startedAt']);
            $this->assertSame(1, $session['sessionsStarted']);

            return true;
        };

        $this->client->expects($this->once())
            ->method('sendSessions')
            ->with($this->callback($expectCallback));

        $this->sessionTracker->startSession();
        $this->sessionTracker->sendSessions();
    }

    public function testSessionsAreBatchedAndSentOnDestruction()
    {
        $expectCallback = function ($payload) {
            $this->assertArrayHasKey('sessionCounts', $payload);
            $this->assertCount(1, $payload['sessionCounts']);

            $session = $payload['sessionCounts'][0];
            $this->assertArrayHasKey('startedAt', $session);
            $this->assertArrayHasKey('sessionsStarted', $session);
            $this->assertRegExp('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $session['startedAt']);
            $this->assertSame(2, $session['sessionsStarted']);

            return true;
        };

        $this->client->expects($this->once())
            ->method('sendSessions')
            ->with($this->callback($expectCallback));

        $this->sessionTracker->startSession();
        $this->sessionTracker->startSession();

        // Trigger the destructor which should result in one call to 'sendSessions'
        // on the HttpClient and a 'sessionsStarted' of '2' in the payload
        unset($this->sessionTracker);
    }

    public function testIfSendingSessionsFailsTheyWillBeResentOnTheNextCallWhenNoRetryFunctionIsGiven()
    {
        $errorLog = $this->getFunctionMock('Bugsnag\SessionTracker', 'error_log');
        $errorLog->expects($this->once())
            ->with("Bugsnag Warning: Couldn't notify. Making sendSessions fail the first time it is called");

        $calls = 0;

        $this->client->expects($this->exactly(2))
            ->method('sendSessions')
            ->with($this->callback(function ($payload) {
                $this->assertArrayHasKey('notifier', $payload);
                $this->assertArrayHasKey('device', $payload);
                $this->assertArrayHasKey('app', $payload);
                $this->assertArrayHasKey('sessionCounts', $payload);

                $this->assertSame($this->config->getNotifier(), $payload['notifier']);
                $this->assertSame($this->config->getDeviceData(), $payload['device']);
                $this->assertSame($this->config->getAppData(), $payload['app']);
                $this->assertCount(1, $payload['sessionCounts']);

                $session = $payload['sessionCounts'][0];

                $this->assertArrayHasKey('startedAt', $session);
                $this->assertArrayHasKey('sessionsStarted', $session);

                $this->assertRegExp('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $session['startedAt']);
                $this->assertSame(1, $session['sessionsStarted']);

                return true;
            }))
            ->willReturnCallback(function () use (&$calls) {
                $calls++;

                if ($calls === 1) {
                    throw new InvalidArgumentException(
                        'Making sendSessions fail the first time it is called'
                    );
                }
            });

        $this->sessionTracker->startSession();

        // This call should fail
        $this->sessionTracker->sendSessions();

        // Trigger the destructor, which should send the sessions again
        unset($this->sessionTracker);
    }

    public function testIfSendingSessionsFailsTheRetryFunctionWillBeCalledIfOneIsGiven()
    {
        $errorLog = $this->getFunctionMock('Bugsnag\SessionTracker', 'error_log');
        $errorLog->expects($this->once())
            ->with("Bugsnag Warning: Couldn't notify. Hello");

        $wasCalled = false;

        $this->client->expects($this->once())
            ->method('sendSessions')
            ->willThrowException(new InvalidArgumentException('Hello'));

        $retryFunction = function (array $sessions) use (&$wasCalled) {
            $this->assertCount(1, $sessions);

            foreach ($sessions as $minute => $count) {
                $this->assertRegExp('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $minute);
                $this->assertSame(1, $count);
            }

            $wasCalled = true;
        };

        $this->sessionTracker->setRetryFunction($retryFunction);

        $this->sessionTracker->startSession();
        $this->sessionTracker->sendSessions();

        $this->assertTrue($wasCalled, 'Expected retryFunction to be called');
    }

    /**
     * @runInSeparateProcess as we need to mock 'strftime'
     */
    public function testThereIsAMaximumNumberOfSessionsThatWillBeSent()
    {
        // 60 sessions is convenient because they are batched per minute, so we
        // can pretend to generate one session per minute for an hour
        $sessionsToGenerate = 60;

        $this->assertGreaterThan(
            SessionTrackerInterface::MAX_SESSION_COUNT,
            $sessionsToGenerate,
            'Expected to generate more sessions than the maximum'
        );

        $expectCallback = function ($payload) use ($sessionsToGenerate) {
            $this->assertArrayHasKey('notifier', $payload);
            $this->assertArrayHasKey('device', $payload);
            $this->assertArrayHasKey('app', $payload);
            $this->assertArrayHasKey('sessionCounts', $payload);

            $this->assertSame($this->config->getNotifier(), $payload['notifier']);
            $this->assertSame($this->config->getDeviceData(), $payload['device']);
            $this->assertSame($this->config->getAppData(), $payload['app']);

            $this->assertCount(SessionTrackerInterface::MAX_SESSION_COUNT, $payload['sessionCounts']);
            $this->assertLessThan($sessionsToGenerate, count($payload['sessionCounts']));

            // Ensure the dates go in decending order of minutes, starting at 59
            $expectedMinute = 59;
            $dateFormat = '2000-01-01T00:%s:00';

            foreach ($payload['sessionCounts'] as $session) {
                $this->assertArrayHasKey('startedAt', $session);
                $this->assertArrayHasKey('sessionsStarted', $session);

                $date = sprintf(
                    $dateFormat,
                    str_pad($expectedMinute, 2, '0', STR_PAD_LEFT)
                );

                $this->assertSame($date, $session['startedAt']);
                $this->assertSame(1, $session['sessionsStarted']);

                $expectedMinute--;
            }

            return true;
        };

        $this->client->expects($this->once())
            ->method('sendSessions')
            ->with($this->callback($expectCallback));

        $strftimeReturnValues = array_map(
            function ($minute) {
                $minute = str_pad($minute, 2, '0', STR_PAD_LEFT);

                return "2000-01-01T00:{$minute}:00";
            },
            // range is inclusive but we need to generate 0-59
            range(0, $sessionsToGenerate - 1)
        );

        $invocation = 0;

        $strftime = $this->getFunctionMock('Bugsnag\\SessionTracker', 'strftime');
        $strftime->expects($this->exactly($sessionsToGenerate))
            ->withAnyParameters()
            ->willReturnCallback(function () use ($strftimeReturnValues, &$invocation) {
                return $strftimeReturnValues[$invocation++];
            });

        for ($i = 0; $i < $sessionsToGenerate; $i++) {
            $this->sessionTracker->startSession();
        }

        $this->sessionTracker->sendSessions();
    }

    public function testSetRetryFunctionThrowsWhenNotGivenACallable()
    {
        $this->expectedException(InvalidArgumentException::class, 'The retry function must be callable');

        $this->sessionTracker->setRetryFunction(null);
    }
}
