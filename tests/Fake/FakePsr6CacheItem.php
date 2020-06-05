<?php

namespace Bugsnag\Tests\Fake;

use BadMethodCallException;
use Psr\Cache\CacheItemInterface;

/**
 * A fake PSR-6 cache item implementation for use in unit tests.
 *
 * This implements the bare minimum methods that we need and will throw an
 * exception if any upimplemented method is called.
 */
final class FakePsr6CacheItem implements CacheItemInterface
{
    private $key;
    private $value;
    private $isHit;

    public function __construct($key, $value, $isHit)
    {
        $this->key = $key;
        $this->value = $value;
        $this->isHit = $isHit;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function get()
    {
        return $this->value;
    }

    public function isHit()
    {
        return $this->isHit;
    }

    public function set($value)
    {
        $this->value = $value;
    }

    public function expiresAt($expiration)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function expiresAfter($time)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }
}
