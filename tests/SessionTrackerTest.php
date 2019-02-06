<?php

namespace Bugsnag\Tests;

use Bugsnag\SessionTracker;
use Bugsnag\Configuration;
use GuzzleHttp\Client as GuzzleClient;
use phpmock\phpunit\PHPMock;
use PHPUnit_Framework_TestCase as TestCase;

class LockMock
{
    public function lock() {}
    public function unlock() {}
}

class RetryMock
{
    public function retry($sessions) {}
}

class StorageMock
{
    public function store($key, $item=null) {}
}

class SessionMock
{
    public function storeSession($session=null) {}
}

class SessionTrackerTest extends TestCase
{
    use PHPMock;

    protected $config;
    protected $guzzleClient;
    protected $sessionTracker;

    protected function setUp()
    {
        $this->config = new Configuration('example_key');
        $this->guzzleClient = $this->getMockBuilder(GuzzleClient::class)
                                   ->setMethods(['post'])
                                   ->getMock();
        $this->config->setGuzzleClient($this->guzzleClient);
        $this->sessionTracker = new SessionTracker($this->config);
    }

    public function testCanStartAndDeliverSession()
    {
        $config = $this->config;
        $this->guzzleClient->expects($this->once())->method('post')->with(
            \Bugsnag\Configuration::DEFAULT_SESSION_ENDPOINT,
            $this->callback(function($subject) use ($config) {
                $json = $subject['json'];
                $headers = $subject['headers'];
                return ($json['notifier'] == $config->getNotifier()) &&
                    ($json['device'] == $config->getDeviceData()) &&
                    ($json['app'] == $config->getAppData()) &&
                    ($json['sessionCounts'][0]['sessionsStarted'] == 1) &&
                    ($headers['Bugsnag-Api-Key'] == 'example_key') &&
                    ($headers['Bugsnag-Sent-At'] != null);
            })
        );
        $this->sessionTracker->startSession();
        $currentTime = strftime('%Y-%m-%dT%H:%M:00');
        $storedSession = $this->sessionTracker->getCurrentSession();
        $this->assertNotNull($storedSession['id']);
        $this->assertSame($currentTime, $storedSession['startedAt']);
        $this->assertSame(['handled' => 0, 'unhandled' => 0], $storedSession['events']);
    }

    public function testCanAddCustomLockFunctions()
    {
        $this->guzzleClient->expects($this->once())->method('post');

        $lockStub = $this->getMockBuilder(LockMock::class)
                         ->setMethods(['lock', 'unlock'])
                         ->getMock();
        $lockStub->expects($this->once())->method('lock');
        $lockStub->expects($this->once())->method('unlock');
        $this->sessionTracker->setLockFunctions([$lockStub, 'lock'], [$lockStub, 'unlock']);

        $this->sessionTracker->startSession();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Both lock and unlock functions must be callable
     */
    public function testCustomLockMustBeCallable()
    {
        $this->sessionTracker->setLockFunctions('lock', 'unlock');
    }

    public function testCanAddCustomRetryFunction()
    {
        $this->guzzleClient->expects($this->once())->method('post')->will($this->throwException(new \Exception("delivery failed")));

        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Couldn\'t notify. delivery failed'));

        $retryStub = $this->getMockBuilder(RetryMock::class)
                         ->setMethods(['retry'])
                         ->getMock();
        $retryStub->expects($this->once())->method('retry');
        $this->sessionTracker->setRetryFunction([$retryStub, 'retry']);

        $this->sessionTracker->startSession();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The retry function must be callable
     */
    public function testCustomRetryMustBeCallable()
    {
        $this->sessionTracker->setRetryFunction('retry');
    }

    public function testCanAddCustomStorageFunction()
    {
        $storageStub = $this->getMockBuilder(StorageMock::class)
                         ->setMethods(['store'])
                         ->getMock();

        $storageStub->expects($this->exactly(5))->method('store')->withConsecutive(
            [
                $this->equalTo('bugsnag-session-counts'),
                $this->equalTo(null)
            ],
            [
                $this->equalTo('bugsnag-session-counts'),
                $this->callback(function($session) {
                    return strtotime(array_keys($session)[0]) &&
                        array_values($session)[0] == 1;
                })
            ],
            [
                $this->equalTo('bugsnag-sessions-last-sent'),
                $this->equalTo(null)
            ],
            [
                $this->equalTo('bugsnag-session-counts'),
                $this->equalTo(null)
            ],
            [
                $this->equalTo('bugsnag-session-counts'),
                $this->equalTo([])
            ]
        )->will($this->onConsecutiveCalls([], null, 0, [], []));
        $this->sessionTracker->setStorageFunction([$storageStub, 'store']);

        $this->sessionTracker->startSession();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Storage function must be callable
     */
    public function testCustomStorageMustBeCallable()
    {
        $this->sessionTracker->setStorageFunction('storage');
    }

    public function testCanAddCustomSessionFunction()
    {
        $this->guzzleClient->expects($this->once())->method('post');

        $sessionStub = $this->getMockBuilder(SessionMock::class)
                         ->setMethods(['storeSession'])
                         ->getMock();
        $sessionStub->expects($this->exactly(2))->method('storeSession')->withConsecutive(
            [
                $this->callback(function($session) {
                    return $session['id'] != null &&
                        strtotime($session['startedAt']) &&
                        $session['events']['handled'] == 0 &&
                        $session['events']['unhandled'] == 0;
                })
            ],
            [
                null
            ]
        );
        $this->sessionTracker->setSessionFunction([$sessionStub, 'storeSession']);

        $this->sessionTracker->startSession();
        $this->sessionTracker->getCurrentSession();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Session function must be callable
     */
    public function testCustomSessionMustBeCallable()
    {
        $this->sessionTracker->setSessionFunction('session');
    }

    public function testChecksSessionsEnabled()
    {
        $this->guzzleClient->expects($this->never())->method('post');
        $this->config->setEndpoints('notify', null);

        $this->sessionTracker->startSession();
    }

}