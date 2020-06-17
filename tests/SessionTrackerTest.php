<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\SessionTracker;
use GuzzleHttp;
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

    public function testSendSessionsEmpty()
    {
        $this->client->expects($this->never())->method('sendSessions');

        $this->sessionTracker->sendSessions();
    }

    public function testHttpClientCanBeObtainedViaConfig()
    {
        /** @var GuzzleHttp\Client&MockObject */
        $guzzle = $this->getMockBuilder(GuzzleHttp\Client::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        /** @var Configuration&MockObject */
        $config = $this->getMockBuilder(Configuration::class)
            ->setConstructorArgs(['example-api-key'])
            ->getMock();

        $config->expects($this->once())
            ->method('getSessionClient')
            ->willReturn($guzzle);

        $config->expects($this->once())
            ->method('getSessionEndpoint')
            ->willReturn(Configuration::SESSION_ENDPOINT);

        $config->expects($this->once())->method('shouldNotify')->willReturn(true);
        $config->expects($this->once())->method('getNotifier')->willReturn('test_notifier');
        $config->expects($this->once())->method('getDeviceData')->willReturn('device_data');
        $config->expects($this->once())->method('getAppData')->willReturn('app_data');

        $expectCallback = function ($payload) use ($config) {
            $this->assertArrayHasKey('json', $payload);
            $this->assertArrayHasKey('headers', $payload);

            $json = $payload['json'];

            $this->assertArrayHasKey('notifier', $json);
            $this->assertArrayHasKey('device', $json);
            $this->assertArrayHasKey('app', $json);
            $this->assertArrayHasKey('sessionCounts', $json);

            $this->assertSame('test_notifier', $json['notifier']);
            $this->assertSame('device_data', $json['device']);
            $this->assertSame('app_data', $json['app']);
            $this->assertCount(1, $json['sessionCounts']);
            $this->assertSame('2000-01-01T00:00:00', $json['sessionCounts'][0]['startedAt']);
            $this->assertSame(1, $json['sessionCounts'][0]['sessionsStarted']);

            return true;
        };

        $method = self::getGuzzleMethod();
        $mock = $guzzle->expects($this->once())->method($method);

        if ($method === 'request') {
            $mock->with('POST', Configuration::SESSION_ENDPOINT, $this->callback($expectCallback));
        } else {
            $mock->with(Configuration::SESSION_ENDPOINT, $this->callback($expectCallback));
        }

        $sessionTracker = new SessionTracker($config);

        $sessionTracker->setStorageFunction(function () {
            return ['2000-01-01T00:00:00' => 1];
        });

        $sessionTracker->sendSessions();
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
        $this->client->expects($this->never())->method('sendSessions');

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
        $this->config->expects($this->never())->method('getNotifier');
        $this->config->expects($this->never())->method('getDeviceData');
        $this->config->expects($this->never())->method('getAppData');
        $this->config->expects($this->never())->method('getApiKey');
        $this->client->expects($this->never())->method('sendSessions');

        $this->sessionTracker->sendSessions();
    }

    /**
     * @param mixed $returnValue
     *
     * @return void
     *
     * @dataProvider storageFunctionEmptyReturnValueProvider
     */
    public function testStartSessionDoesNotDeliverSessionsWhenLastSentIsNotAnInteger($returnValue)
    {
        $this->sessionTracker->setStorageFunction(function ($key) use ($returnValue) {
            // We only care about the "last sent" value here
            if ($key === 'bugsnag-sessions-last-sent') {
                return $returnValue;
            }

            return null;
        });

        $this->config->expects($this->never())->method('shouldNotify');
        $this->config->expects($this->never())->method('getNotifier');
        $this->config->expects($this->never())->method('getDeviceData');
        $this->config->expects($this->never())->method('getAppData');
        $this->config->expects($this->never())->method('getApiKey');
        $this->client->expects($this->never())->method('sendSessions');

        $this->sessionTracker->startSession();
    }

    /**
     * @param mixed $returnValue
     *
     * @return void
     *
     * @dataProvider storageFunctionEmptyReturnValueProvider
     */
    public function testStartSessionDoesNotDeliverSessionsWhenGetSessionCountsReturnsAValueThatsNotAPopulatedArray($returnValue)
    {
        $this->sessionTracker->setStorageFunction(function ($key) use ($returnValue) {
            return $returnValue;
        });

        $this->config->expects($this->never())->method('shouldNotify');
        $this->config->expects($this->never())->method('getNotifier');
        $this->config->expects($this->never())->method('getDeviceData');
        $this->config->expects($this->never())->method('getAppData');
        $this->config->expects($this->never())->method('getApiKey');
        $this->client->expects($this->never())->method('sendSessions');

        $this->sessionTracker->startSession();
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

    public function testSendSessionsSuccess()
    {
        $this->sessionTracker->setStorageFunction(function () {
            return ['2000-01-01T00:00:00' => 1];
        });

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
                && $payload['sessionCounts'][0]['startedAt'] === '2000-01-01T00:00:00'
                && $payload['sessionCounts'][0]['sessionsStarted'] === 1;
        };

        $this->client->expects($this->once())
            ->method('sendSessions')
            ->with($this->callback($expectCallback));

        $this->sessionTracker->sendSessions();
    }

    public function testStartSessionsSuccess()
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

    public function testSetLockFunctionsThrowsWhenBothFunctionsAreNotCallable()
    {
        $this->expectedException(InvalidArgumentException::class, 'Both lock and unlock functions must be callable');

        $this->sessionTracker->setLockFunctions(null, function () {});
    }

    public function testSetLockFunctionsThrowsWhenLockIsNotCallable()
    {
        $this->expectedException(InvalidArgumentException::class, 'Both lock and unlock functions must be callable');

        $this->sessionTracker->setLockFunctions(null, function () {});
    }

    public function testSetLockFunctionsThrowsWhenUnlockIsNotCallable()
    {
        $this->expectedException(InvalidArgumentException::class, 'Both lock and unlock functions must be callable');

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
        $this->expectedException(InvalidArgumentException::class, 'The retry function must be callable');

        $this->sessionTracker->setRetryFunction(null);
    }

    public function testSetStorageFunctionThrowsWhenNotGivenACallable()
    {
        $this->expectedException(InvalidArgumentException::class, 'Storage function must be callable');

        $this->sessionTracker->setStorageFunction(null);
    }

    public function testSetSessionFunctionThrowsWhenNotGivenACallable()
    {
        $this->expectedException(InvalidArgumentException::class, 'Session function must be callable');

        $this->sessionTracker->setSessionFunction(null);
    }
}
