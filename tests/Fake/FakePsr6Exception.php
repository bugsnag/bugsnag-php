<?php

namespace Bugsnag\Tests\Fake;

use Psr\Cache\InvalidArgumentException;
use RuntimeException;

/**
 * A fake PSR-6 exception for use by the fake PSR-6 implementation.
 */
final class FakePsr6Exception extends RuntimeException implements InvalidArgumentException
{
}
