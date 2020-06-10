<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Bugsnag\Middleware\SessionData;
use Bugsnag\Report;
use Bugsnag\SessionTracker\NullSessionTracker;
use Bugsnag\SessionTracker\SessionTracker;
use Bugsnag\SessionTracker\SessionTrackerInterface;
use Bugsnag\Tests\TestCase;
use Exception;

class SessionDataTest extends TestCase
{
    public function sessionTrackerProvider()
    {
        $config = new Configuration('api-key');
        $httpClient = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            'real session tracker' => [
                new SessionTracker($config, $httpClient),
                Report::fromPHPThrowable($config, new Exception('no')),
            ],
            'null session tracker' => [
                new NullSessionTracker(),
                Report::fromPHPThrowable($config, new Exception('no')),
            ],
        ];
    }

    /**
     * @param SessionTrackerInterface $sessionTracker
     * @param Report                  $report
     *
     * @return void
     *
     * @dataProvider sessionTrackerProvider
     */
    public function testItSetsTheUnhandledCountWhenAnUnhandledErrorOccurs(
        SessionTrackerInterface $sessionTracker,
        Report $report
    ) {
        $sessionTracker->startSession();
        $report->setUnhandled(true);

        $middleware = new SessionData($sessionTracker);

        $middleware($report, function (Report $report) {
            $this->assertReportHasUnhandledErrors($report, 1);
        });
    }

    /**
     * @param SessionTrackerInterface $sessionTracker
     * @param Report                  $report
     *
     * @return void
     *
     * @dataProvider sessionTrackerProvider
     */
    public function testItIncrementsTheUnhandledCountWhenMultipleUnhandledErrorsOccur(
        SessionTrackerInterface $sessionTracker,
        Report $report
    ) {
        $sessionTracker->startSession();
        $report->setUnhandled(true);

        $middleware = new SessionData($sessionTracker);

        foreach (range(1, 5) as $errorCount) {
            $middleware($report, function (Report $report) use ($errorCount) {
                $this->assertReportHasUnhandledErrors($report, $errorCount);
            });
        }
    }

    /**
     * @param SessionTrackerInterface $sessionTracker
     * @param Report                  $report
     *
     * @return void
     *
     * @dataProvider sessionTrackerProvider
     */
    public function testItSetsTheHandledCountWhenAHandledErrorOccurs(
        SessionTrackerInterface $sessionTracker,
        Report $report
    ) {
        $sessionTracker->startSession();
        $report->setUnhandled(false);

        $middleware = new SessionData($sessionTracker);

        $middleware($report, function (Report $report) {
            $this->assertReportHasHandledErrors($report, 1);
        });
    }

    /**
     * @param SessionTrackerInterface $sessionTracker
     * @param Report                  $report
     *
     * @return void
     *
     * @dataProvider sessionTrackerProvider
     */
    public function testItIncrementsTheHandledCountWhenMultipleHandledErrorsOccur(
        SessionTrackerInterface $sessionTracker,
        Report $report
    ) {
        $sessionTracker->startSession();
        $report->setUnhandled(false);

        $middleware = new SessionData($sessionTracker);

        foreach (range(1, 5) as $errorCount) {
            $middleware($report, function (Report $report) use ($errorCount) {
                $this->assertReportHasHandledErrors($report, $errorCount);
            });
        }
    }

    /**
     * @param SessionTrackerInterface $sessionTracker
     * @param Report                  $report
     *
     * @return void
     *
     * @dataProvider sessionTrackerProvider
     */
    public function testItDoesNothingWhenForAnUnhandledErrorWhenThereIsNoSessionData(
        SessionTrackerInterface $sessionTracker,
        Report $report
    ) {
        // We don't call 'startSession' here so there is no session data to
        // capture in the middleware
        $report->setUnhandled(true);

        $middleware = new SessionData($sessionTracker);

        $middleware($report, function (Report $report) {
            $reportArray = $report->toArray();
            $this->assertArrayNotHasKey('session', $reportArray);
        });
    }

    /**
     * @param SessionTrackerInterface $sessionTracker
     * @param Report                  $report
     *
     * @return void
     *
     * @dataProvider sessionTrackerProvider
     */
    public function testItDoesNothingWhenForAHandledErrorWhenThereIsNoSessionData(
        SessionTrackerInterface $sessionTracker,
        Report $report
    ) {
        // We don't call 'startSession' here so there is no session data to
        // capture in the middleware
        $report->setUnhandled(false);

        $middleware = new SessionData($sessionTracker);

        $middleware($report, function (Report $report) {
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
