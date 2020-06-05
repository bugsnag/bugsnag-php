<?php

namespace Bugsnag\Tests\Fake;

use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;

/**
 * A fake PSR-16 exception for use by the fake PSR-16 implementation.
 */
final class FakePsr16Exception extends RuntimeException implements InvalidArgumentException
{
}
