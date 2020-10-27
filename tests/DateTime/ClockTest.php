<?php

namespace Bugsnag\Tests\DateTime;

use Bugsnag\DateTime\Clock;
use Bugsnag\DateTime\ClockInterface;
use Bugsnag\Tests\TestCase;
use DateTimeImmutable;

class ClockTest extends TestCase
{
    public function testItImplementsClockInterface()
    {
        $clock = new Clock();

        $this->assertInstanceOf(ClockInterface::class, $clock);
    }

    public function testItReturnsADateTimeImmutable()
    {
        $clock = new Clock();

        $this->assertInstanceOf(DateTimeImmutable::class, $clock->now());
    }

    public function testItReturnsTheCurrentDateTime()
    {
        // We need to sleep between creating date objects, otherwise we hit
        // issues due to $now possibly being equal to $before

        // Sleeping for 5ms is enough on PHP 7.1+
        $timeToSleep = 5000;

        // Before PHP 7.1, microseconds were not included when constructing date
        // objects, so we need to sleep for an entire second
        if (version_compare(PHP_VERSION, '7.1.0', '<')) {
            $timeToSleep = 1000 * 1000;
        }

        $clock = new Clock();

        $before = new DateTimeImmutable();

        usleep($timeToSleep);

        $now = $clock->now();

        usleep($timeToSleep);

        $after = new DateTimeImmutable();

        $this->assertGreaterThan($before, $now);
        $this->assertLessThan($after, $now);
    }
}
