<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\SessionTracker;
use phpmock\phpunit\PHPMock;
use PHPUnit_Framework_TestCase as TestCase;

class SessionTrackerTest extends TestCase
{
    use PHPMock;

    protected $sessionTracker;
    protected $config;
    protected $http;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(Configuration::class)
                            ->setConstructorArgs(['example-api-key'])
                            ->getMock();
        $this->sessionTracker = $this->getMockBuilder(SessionTracker::class)
                             ->setMethods(['getSessionCounts', 'setLastSent'])
                             ->setConstructorArgs([$this->config])
                             ->getMock();
        $this->http = $this->getMockBuilder(HttpClient::class)
                            ->setMethods(['post'])
                            ->getMock();
    }

    public function testSendSessionsEmpty()
    {
        $this->sessionTracker->expects($this->once())->method('getSessionCounts')->willReturn([]);
        $this->config->expects($this->never())->method('getSessionClient');
        $this->http->expects($this->never())->method('post');

        $this->sessionTracker->sendSessions();
    }

    public function testSendSessionsShouldNotNotify()
    {
        $this->sessionTracker->expects($this->once())->method('getSessionCounts')->willReturn(['2000-01-01T00:00:00' => 1]);
        $this->config->expects($this->once())->method('shouldNotify')->willReturn(false);
        $this->config->expects($this->never())->method('getSessionClient');
        $this->http->expects($this->never())->method('post');

        $this->sessionTracker->sendSessions();
    }

    public function testSendSessions()
    {
        $this->sessionTracker->expects($this->once())->method('getSessionCounts')->willReturn(['2000-01-01T00:00:00' => 1]);
        $this->config->expects($this->once())->method('shouldNotify')->willReturn(true);
        $this->config->expects($this->once())->method('getSessionClient')->willReturn($this->http);
        $this->config->expects($this->once())->method('getNotifier')->willReturn('test_notifier');
        $this->config->expects($this->once())->method('getDeviceData')->willReturn('device_data');
        $this->config->expects($this->once())->method('getAppData')->willReturn('app_data');
        $this->config->expects($this->once())->method('getApiKey')->willReturn('example-api-key');
        $this->sessionTracker->expects($this->once())->method('setLastSent');

        $this->http->expects($this->once())->method('post')->with($this->equalTo(''), $this->callback(function ($sessionPayload) {
            return count($sessionPayload) == 2
                   && $sessionPayload['json']['notifier'] == 'test_notifier'
                   && $sessionPayload['json']['device'] == 'device_data'
                   && $sessionPayload['json']['app'] == 'app_data'
                   && count($sessionPayload['json']['sessionCounts']) == 1
                   && $sessionPayload['json']['sessionCounts'][0]['startedAt'] == '2000-01-01T00:00:00'
                   && $sessionPayload['json']['sessionCounts'][0]['sessionsStarted'] == 1
                   && $sessionPayload['headers']['Bugsnag-Api-Key'] == 'example-api-key'
                   && preg_match('/(\d+\.)+/', $sessionPayload['headers']['Bugsnag-Payload-Version'])
                   && preg_match('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $sessionPayload['headers']['Bugsnag-Sent-At']);
        }));

        $this->sessionTracker->sendSessions();
    }
}
