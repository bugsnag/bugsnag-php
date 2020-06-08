<?php

namespace Bugsnag\Tests\Fake;

use BadMethodCallException;
use DateInterval;
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
    private $isHit = false;
    private $expiresAt;

    public function __construct($key)
    {
        $this->key = $key;
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
        return time() < $this->expiresAt && $this->isHit;
    }

    public function set($value)
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt($expiration)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function expiresAfter($expiresAfter)
    {
        if ($expiresAfter instanceof DateInterval) {
            throw new BadMethodCallException(
                __METHOD__.' is not implemented for DateInterval objects'
            );
        }

        $this->expiresAt = time() + $expiresAfter;

        return $this;
    }

    public function save()
    {
        $this->isHit = true;

        return $this;
    }
}
