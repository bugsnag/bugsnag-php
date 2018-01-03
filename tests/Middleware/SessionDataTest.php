<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Client;
use Bugsnag\Middleware\SessionData;
use Bugsnag\Report;
use Bugsnag\SessionTracker;
use GrahamCampbell\TestBenchCore\MockeryTrait;
use Mockery;
use PHPUnit_Framework_TestCase as TestCase;

class SessionDataTest extends TestCase
{
    use MockeryTrait;

    public function testNoTracking()
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('shouldTrackSessions')->andReturn(false);

        $report = Mockery::mock(Report::class);

        $middleware = new SessionData($client);
        $middleware($report, function ($var) use ($report) {
            $this->assertSame($var, $report);
        });
    }

    public function testUnhandledError()
    {
        $sessionTracker = Mockery::mock(SessionTracker::class);
        $sessionTracker->shouldReceive('getCurrentSession')->andReturn([
            'events' => [
                'unhandled' => 0,
                'handled' => 0,
            ],
        ]);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('shouldTrackSessions')->andReturn(true);
        $client->shouldReceive('getSessionTracker')->andReturn($sessionTracker);

        $report = Mockery::mock(Report::class);
        $report->shouldReceive('getUnhandled')->andReturn(true);
        $report->shouldReceive('setSessionData')->with([
            'events' => [
                'unhandled' => 1,
                'handled' => 0,
            ],
        ]);

        $middleware = new SessionData($client);
        $middleware($report, function ($var) use ($report) {
            $this->assertSame($var, $report);
        });
    }

    public function testHandledError()
    {
        $sessionTracker = Mockery::mock(SessionTracker::class);
        $sessionTracker->shouldReceive('getCurrentSession')->andReturn([
            'events' => [
                'unhandled' => 0,
                'handled' => 0,
            ],
        ]);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('shouldTrackSessions')->andReturn(true);
        $client->shouldReceive('getSessionTracker')->andReturn($sessionTracker);

        $report = Mockery::mock(Report::class);
        $report->shouldReceive('getUnhandled')->andReturn(false);
        $report->shouldReceive('setSessionData')->with([
            'events' => [
                'unhandled' => 0,
                'handled' => 1,
            ],
        ]);

        $middleware = new SessionData($client);
        $middleware($report, function ($var) use ($report) {
            $this->assertSame($var, $report);
        });
    }
}
