<?php

namespace Bugsnag\Tests\Fakes;

use Bugsnag\DateTime\ClockInterface;
use DateTimeImmutable;

final class FakeClock implements ClockInterface
{
    private $date;

    public function __construct(DateTimeImmutable $date)
    {
        $this->date = $date;
    }

    public function now()
    {
        return $this->date;
    }
}
