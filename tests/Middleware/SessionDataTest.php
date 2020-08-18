<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Client;
use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\Middleware\SessionData;
use Bugsnag\Report;
use Bugsnag\SessionTracker;
use Bugsnag\Tests\TestCase;
use Exception;

class SessionDataTest extends TestCase
{
    /**
     * @var Client&\PHPUnit\Framework\MockObject
     */
    private $client;

    /**
     * @var SessionTracker
     */
    private $sessionTracker;

    /**
     * @var Report
     */
    private $report;

    protected function setUp()
    {
        $config = new Configuration('api-key');

        /** @var HttpClient&\PHPUnit\Framework\MockObject $httpClient */
        $httpClient = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionTracker = new SessionTracker($config, $httpClient);

        /** @var Client&\PHPUnit\Framework\MockObject $httpClient */
        $this->client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $this->client->method('getSessionTracker')->willReturn($this->sessionTracker);

        $this->report = Report::fromPHPThrowable($config, new Exception('no'));
    }

    public function testItSetsTheUnhandledCountWhenAnUnhandledErrorOccurs()
    {
        $this->sessionTracker->startSession();
        $this->report->setUnhandled(true);

        $middleware = new SessionData($this->client);

        $middleware($this->report, function (Report $report) {
            $this->assertReportHasUnhandledErrors($report, 1);
        });
    }

    public function testItIncrementsTheUnhandledCountWhenMultipleUnhandledErrorsOccur()
    {
        $this->sessionTracker->startSession();
        $this->report->setUnhandled(true);

        $middleware = new SessionData($this->client);

        foreach (range(1, 5) as $errorCount) {
            $middleware($this->report, function (Report $report) use ($errorCount) {
                $this->assertReportHasUnhandledErrors($report, $errorCount);
            });
        }
    }

    public function testItSetsTheHandledCountWhenAHandledErrorOccurs()
    {
        $this->sessionTracker->startSession();
        $this->report->setUnhandled(false);

        $middleware = new SessionData($this->client);

        $middleware($this->report, function (Report $report) {
            $this->assertReportHasHandledErrors($report, 1);
        });
    }

    public function testItIncrementsTheHandledCountWhenMultipleHandledErrorsOccur()
    {
        $this->sessionTracker->startSession();
        $this->report->setUnhandled(false);

        $middleware = new SessionData($this->client);

        foreach (range(1, 5) as $errorCount) {
            $middleware($this->report, function (Report $report) use ($errorCount) {
                $this->assertReportHasHandledErrors($report, $errorCount);
            });
        }
    }

    public function testItDoesNothingWhenForAnUnhandledErrorWhenThereIsNoSessionData()
    {
        // We don't call 'startSession' here so there is no session data to
        // capture in the middleware
        $this->report->setUnhandled(true);

        $middleware = new SessionData($this->client);

        $middleware($this->report, function (Report $report) {
            $reportArray = $report->toArray();
            $this->assertArrayNotHasKey('session', $reportArray);
        });
    }

    public function testItDoesNothingWhenForAHandledErrorWhenThereIsNoSessionData()
    {
        // We don't call 'startSession' here so there is no session data to
        // capture in the middleware
        $this->report->setUnhandled(false);

        $middleware = new SessionData($this->client);

        $middleware($this->report, function (Report $report) {
            $reportArray = $report->toArray();
            $this->assertArrayNotHasKey('session', $reportArray);
        });
    }

    /**
     * Assert the given report has the expected number of unhandled errors.
     *
     * @param Report $report
     * @param int    $expectedCount
     *
     * @return void
     */
    private function assertReportHasUnhandledErrors(Report $report, $expectedCount)
    {
        $this->assertReportHasErrors($report, 'unhandled', $expectedCount);
    }

    /**
     * Assert the given report has the expected number of handled errors.
     *
     * @param Report $report
     * @param int    $expectedCount
     *
     * @return void
     */
    private function assertReportHasHandledErrors(Report $report, $expectedCount)
    {
        $this->assertReportHasErrors($report, 'handled', $expectedCount);
    }

    /**
     * Assert the given report has the expected number of errors of type '$type'.
     *
     * @psalm-param 'handled'|'unhandled' $type
     *
     * @param Report $report
     * @param string $type          one of 'handled' or 'unhandled'
     * @param int    $expectedCount
     *
     * @return void
     */
    private function assertReportHasErrors(Report $report, $type, $expectedCount)
    {
        $reportArray = $report->toArray();
        $this->assertArrayHasKey('session', $reportArray);

        $session = $reportArray['session'];
        $this->assertArrayHasKey('events', $session);

        $events = $session['events'];
        $this->assertArrayHasKey('handled', $events);
        $this->assertArrayHasKey('unhandled', $events);

        if ($type === 'handled') {
            $this->assertSame($expectedCount, $events['handled']);
            $this->assertSame(0, $events['unhandled']);
        } else {
            $this->assertSame(0, $events['handled']);
            $this->assertSame($expectedCount, $events['unhandled']);
        }
    }
}
