<?php

namespace Bugsnag\Tests\SessionTracker;

use Bugsnag\Configuration;
use Bugsnag\Report;
use Bugsnag\SessionTracker\CurrentSession;
use Bugsnag\Tests\TestCase;
use Exception;

class CurrentSessionTest extends TestCase
{
    public function testIsActiveReturnsFalseBeforeSessionIsStarted()
    {
        $session = new CurrentSession();

        $this->assertFalse($session->isActive());
    }

    public function testIsActiveReturnsTrueOnceSessionIsStarted()
    {
        $session = new CurrentSession();

        $session->start('2000-01-01T12:00:00');

        $this->assertTrue($session->isActive());
    }

    public function testToArrayReturnsSessionDataInTheExpectedFormat()
    {
        $session = new CurrentSession();
        $session->start('2000-01-01T12:00:00');

        $actual = $session->toArray();

        $this->assertArrayHasKey('id', $actual);
        $this->assertArrayHasKey('startedAt', $actual);
        $this->assertArrayHasKey('events', $actual);

        $this->assertSame('2000-01-01T12:00:00', $actual['startedAt']);
        $this->assertSame(['handled' => 0, 'unhandled' => 0], $actual['events']);
    }

    public function testStartedAtChangesWhenSessionIsStartedMultipleTimes()
    {
        $session = new CurrentSession();
        $session->start('2000-01-01T12:00:00');
        $session->start('2001-01-01T12:00:00');
        $session->start('2002-01-01T12:00:00');

        $actual = $session->toArray();

        $this->assertSame('2002-01-01T12:00:00', $actual['startedAt']);
    }

    public function testHandledCountIsIncrementedWhenGivenAHandledReport()
    {
        $report = Report::fromPHPThrowable(new Configuration('hello'), new Exception('hi'));
        $report->setUnhandled(false);

        $session = new CurrentSession();
        $session->start('2000-01-01T12:00:00');
        $session->handle($report);

        $actual = $session->toArray();

        $this->assertSame(['handled' => 1, 'unhandled' => 0], $actual['events']);
    }

    public function testHandledCountIsIncrementedWhenGivenMultipleHandledReports()
    {
        $report = Report::fromPHPThrowable(new Configuration('hello'), new Exception('hi'));
        $report->setUnhandled(false);

        $session = new CurrentSession();
        $session->start('2000-01-01T12:00:00');
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);

        $actual = $session->toArray();

        $this->assertSame(['handled' => 5, 'unhandled' => 0], $actual['events']);
    }

    public function testHandledCountIsNotIncrementedIfSessionHasNotStarted()
    {
        $report = Report::fromPHPThrowable(new Configuration('hello'), new Exception('hi'));
        $report->setUnhandled(false);

        $session = new CurrentSession();
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);

        $actual = $session->toArray();

        $this->assertSame(['handled' => 0, 'unhandled' => 0], $actual['events']);
    }

    public function testUnhandledCountIsIncrementedWhenGivenAUnhandledReport()
    {
        $report = Report::fromPHPThrowable(new Configuration('hello'), new Exception('hi'));
        $report->setUnhandled(true);

        $session = new CurrentSession();
        $session->start('2000-01-01T12:00:00');
        $session->handle($report);

        $actual = $session->toArray();

        $this->assertSame(['handled' => 0, 'unhandled' => 1], $actual['events']);
    }

    public function testUnhandledCountIsIncrementedWhenGivenMultipleUnhandledReports()
    {
        $report = Report::fromPHPThrowable(new Configuration('hello'), new Exception('hi'));
        $report->setUnhandled(true);

        $session = new CurrentSession();
        $session->start('2000-01-01T12:00:00');
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);

        $actual = $session->toArray();

        $this->assertSame(['handled' => 0, 'unhandled' => 5], $actual['events']);
    }

    public function testUnhandledCountIsNotIncrementedIfSessionHasNotStarted()
    {
        $report = Report::fromPHPThrowable(new Configuration('hello'), new Exception('hi'));
        $report->setUnhandled(true);

        $session = new CurrentSession();
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);
        $session->handle($report);

        $actual = $session->toArray();

        $this->assertSame(['handled' => 0, 'unhandled' => 0], $actual['events']);
    }
}
