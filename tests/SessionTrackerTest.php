<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\SessionTracker;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
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
        $this->guzzleClient->expects($this->never())->method($this->getGuzzleMethod());

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

        $expectCallback = function ($sessionPayload) {
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
        };

        if ($this->getGuzzleMethod() === 'post') {
            $this->guzzleClient->expects($this->once())
                ->method($this->getGuzzleMethod())
                ->with('', $this->callback($expectCallback));
        } else {
            $this->guzzleClient->expects($this->once())
                ->method($this->getGuzzleMethod())
                ->with('POST', '', $this->callback($expectCallback));
        }

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

        $expectCallback = function ($sessionPayload) {
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
        };

        if ($this->getGuzzleMethod() === 'post') {
            $this->guzzleClient->expects($this->once())
                ->method($this->getGuzzleMethod())
                ->with('', $this->callback($expectCallback));
        } else {
            $this->guzzleClient->expects($this->once())
                ->method($this->getGuzzleMethod())
                ->with('POST', '', $this->callback($expectCallback));
        }

        $this->sessionTracker->startSession();
    }

    public function testSetLockFunctionsThrowsWhenBothFunctionsAreNotCallable()
    {
        $this->expectExceptionObject(new InvalidArgumentException('Both lock and unlock functions must be callable'));

        $this->sessionTracker->setLockFunctions(null, function () {});
    }

    public function testSetLockFunctionsThrowsWhenLockIsNotCallable()
    {
        $this->expectExceptionObject(new InvalidArgumentException('Both lock and unlock functions must be callable'));

        $this->sessionTracker->setLockFunctions(null, function () {});
    }

    public function testSetLockFunctionsThrowsWhenUnlockIsNotCallable()
    {
        $this->expectExceptionObject(new InvalidArgumentException('Both lock and unlock functions must be callable'));

        $this->sessionTracker->setLockFunctions(function () {}, null);
    }

    public function testSetLockFunctionsSucceedsWhenBothFunctionsAreCallable()
    {
        $locked = false;
        $lockWasCalled = false;
        $unlockWasCalled = false;

        $this->sessionTracker->setLockFunctions(
            function () use (&$locked, &$lockWasCalled) {
                $locked = true;
                $lockWasCalled = true;
            },
            function () use (&$locked, &$unlockWasCalled) {
                $locked = false;
                $unlockWasCalled = true;
            }
        );

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

        $this->sessionTracker->startSession();

        $this->assertFalse($locked, 'Expected not to be locked after sending sessions');
        $this->assertTrue($lockWasCalled, 'Expected the `lockFunction` to be called');
        $this->assertTrue($unlockWasCalled, 'Expected the `unlockFunction` to be called');
    }

    public function testSessionShouldBeUnlockedAfterAnException()
    {
        $locked = false;
        $lockWasCalled = false;
        $unlockWasCalled = false;

        $this->sessionTracker->setLockFunctions(
            function () use (&$locked, &$lockWasCalled) {
                $locked = true;
                $lockWasCalled = true;
            },
            function () use (&$locked, &$unlockWasCalled) {
                $locked = false;
                $unlockWasCalled = true;
            }
        );

        $this->sessionTracker->setStorageFunction(function () {
            throw new RuntimeException('Something went wrong!');
        });

        $this->config->expects($this->never())->method('shouldNotify');

        $e = null;

        try {
            $this->sessionTracker->startSession();
        } catch (RuntimeException $e) {
            $this->assertSame('Something went wrong!', $e->getMessage());
        }

        $this->assertNotNull($e, 'Expected a RuntimeException to be thrown');
        $this->assertFalse($locked, 'Expected not to be locked after failing to send sessions');
        $this->assertTrue($lockWasCalled, 'Expected the `lockFunction` to be called');
        $this->assertTrue($unlockWasCalled, 'Expected the `unlockFunction` to be called');
    }

    public function testSetRetryFunctionThrowsWhenNotGivenACallable()
    {
        $this->expectExceptionObject(new InvalidArgumentException('The retry function must be callable'));

        $this->sessionTracker->setRetryFunction(null);
    }

    public function testSetStorageFunctionThrowsWhenNotGivenACallable()
    {
        $this->expectExceptionObject(new InvalidArgumentException('Storage function must be callable'));

        $this->sessionTracker->setStorageFunction(null);
    }

    public function testSetSessionFunctionThrowsWhenNotGivenACallable()
    {
        $this->expectExceptionObject(new InvalidArgumentException('Session function must be callable'));

        $this->sessionTracker->setSessionFunction(null);
    }
}
