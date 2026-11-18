<?php

namespace Bugsnag\Tests\Fakes;

use Bugsnag\Stacktrace;

/**
 * A stacktrace that asks for more lines than the file contains, simulating a
 * file that shrinks between counting its lines and reading the source context.
 */
final class StacktraceWithBoundsPastEof extends Stacktrace
{
    protected static function getBounds($line, $num, $max)
    {
        return [$line, $line + $num + 100];
    }
}
