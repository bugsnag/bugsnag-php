<?php

namespace Bugsnag\Tests\DateTime;

use Bugsnag\DateTime\Date;
use Bugsnag\Tests\Fakes\FakeClock;
use Bugsnag\Tests\TestCase;
use DateTimeImmutable;
use DateTimeZone;

class DateTest extends TestCase
{
    /**
     * @dataProvider dateProvider
     *
     * @param string $dateString
     * @param string $offset
     * @param string $expected
     *
     * @return void
     */
    public function testItFormatsTheDateCorrectly($dateString, $offset, $expected)
    {
        $date = new DateTimeImmutable($dateString, new DateTimeZone($offset));
        $clock = new FakeClock($date);

        $this->assertSame($expected, Date::now($clock));
    }

    public function testItReturnsTheCurrentDateTimeWithARealClock()
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

        $before = new DateTimeImmutable();

        usleep($timeToSleep);

        $now = new DateTimeImmutable(Date::now());

        usleep($timeToSleep);

        $after = new DateTimeImmutable();

        $this->assertGreaterThan($before, $now);
        $this->assertLessThan($after, $now);
    }

    public function dateProvider()
    {
        return [
            ['2020-01-02 03:04:05.678912', '+0000', '2020-01-02T03:04:05.678+00:00'],
            ['2020-01-02 03:04:05.678912', '+1000', '2020-01-02T03:04:05.678+10:00'],
            ['2020-01-02 03:04:05.678912', '+1234', '2020-01-02T03:04:05.678+12:34'],
            ['2020-01-02 03:04:05.678912', '-1234', '2020-01-02T03:04:05.678-12:34'],
            ['1900-12-31 19:00:12.311900', '+1900', '1900-12-31T19:00:12.311+19:00'],
        ];
    }
}
