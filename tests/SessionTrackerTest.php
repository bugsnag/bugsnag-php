<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\SessionTracker;
use GrahamCampbell\TestBenchCore\MockeryTrait;
use GuzzleHttp\Client as GuzzleClient;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

class SessionTrackerTest extends TestCase
{
    use MockeryTrait;

    protected $sessions = [];

    protected function store($key, $value = null)
    {
        if (is_null($value)) {
            switch ($key) {
                case 'bugsnag-sessions-last-sent':
                    return time();
                case 'bugsnag-session-counts':
                    return $this->sessions;
            }
        } else {
            if ($key == 'bugsnag-session-counts') {
                $this->sessions = $value;
            }
        }
    }

    /**
     * @before
     */
    protected function resetSessions()
    {
        $this->sessions = [];
    }

    public function testIncrementsCount()
    {
        $config = Mockery::mock(Configuration::class);
        $sessionTracker = new SessionTracker($config);
        $sessionTracker->setStorageFunction(function ($key, $value = null) {
            return $this->store($key, $value);
        });
        $this->assertSame([], $this->sessions);
        $sessionTracker->startSession();
        $this->assertSame(1, count($this->sessions));
        $key = array_keys($this->sessions)[0];
        $this->assertSame(1, $this->sessions[$key]);
    }

    public function testStoresCurrentSession()
    {
        $config = Mockery::mock(Configuration::class);
        $sessionTracker = new SessionTracker($config);
        $sessionTracker->setStorageFunction(function ($key, $value = null) {
            return $this->store($key, $value);
        });
        $session = $sessionTracker->getCurrentSession();
        $this->assertNull($session);

        $sessionTracker->startSession();
        $session = $sessionTracker->getCurrentSession();
        $this->assertArrayHasKey('id', $session);
        $this->assertArrayHasKey('startedAt', $session);
        $this->assertArrayHasKey('events', $session);
        $this->assertSame([
            'handled' => 0,
            'unhandled' => 0,
        ], $session['events']);
    }

    public function testGivesDifferentIdsToSessions()
    {
        $config = Mockery::mock(Configuration::class);
        $sessionTracker = new SessionTracker($config);
        $sessionTracker->setStorageFunction(function ($key, $value = null) {
            return $this->store($key, $value);
        });
        $session = $sessionTracker->getCurrentSession();
        $this->assertNull($session);

        $sessionTracker->startSession();
        $sessionA = $sessionTracker->getCurrentSession();
        $sessionTracker->startSession();
        $sessionB = $sessionTracker->getCurrentSession();

        $this->assertFalse($sessionA['id'] == $sessionB['id']);
    }

    public function testSendsSessionWhenSendSessionsIsCalled()
    {
        $config = Mockery::mock(Configuration::class);
        $sessionTracker = new SessionTracker($config);
        $sessionTracker->setStorageFunction(function ($key, $value = null) {
            return $this->store($key, $value);
        });

        $httpClient = Mockery::mock(GuzzleClient::class);
        $config->shouldReceive('getSessionClient')
            ->once()
            ->andReturn($httpClient);
        $config->shouldReceive('getApiKey')
            ->once()
            ->andReturn('api_key');
        $config->shouldReceive('getNotifier')
            ->once()
            ->andReturn('notifier');
        $config->shouldReceive('getDeviceData')
            ->once()
            ->andReturn('device_data');
        $config->shouldReceive('getAppData')
            ->once()
            ->andReturn('app_data');
        $httpClient->shouldReceive('post')
            ->once()
            ->with('', \Mockery::on(function ($options) {
                $testApiKey = $options['headers']['Bugsnag-Api-Key'] == 'api_key';
                $testPayloadVersion = $options['headers']['Bugsnag-Payload-Version'] == '1.0';
                $testSentAt = array_key_exists('Bugsnag-Sent-At', $options['headers']);
                $testHeaders = $testApiKey && $testPayloadVersion;

                $testNotifier = $options['json']['notifier'] == 'notifier';
                $testDevice = $options['json']['device'] == 'device_data';
                $testApp = $options['json']['app'] == 'app_data';

                $testSessionCounts = count($options['json']['sessionCounts']) == 1;
                $testSessionStartedAt = array_key_exists('startedAt', $options['json']['sessionCounts'][0]);
                $testSessionSessionsStarted = $options['json']['sessionCounts'][0]['sessionsStarted'] == 1;
                $testSessions = $testSessionCounts && $testSessionStartedAt && $testSessionSessionsStarted;

                $testPayload = $testNotifier && $testDevice && $testApp && $testSessions;

                return $testPayload && $testHeaders;
            }));

        $sessionTracker->startSession();
        $sessionTracker->sendSessions();
    }
}
