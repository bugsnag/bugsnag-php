<?php

namespace Bugsnag\Tests\Fake;

use BadMethodCallException;
use Psr\SimpleCache\CacheInterface;

/**
 * A fake PSR-16 implementation for use in unit tests.
 *
 * This implements the bare minimum methods that we need and will throw an
 * exception if any upimplemented method is called.
 */
final class FakePsr16Cache implements CacheInterface
{
    /**
     * @var array
     */
    private $cache = [];

    public function get($key, $default = null)
    {
        if (!is_string($key)) {
            throw new FakePsr16Exception('Invalid key given');
        }

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        if (!is_string($key)) {
            throw new FakePsr16Exception('Invalid key given');
        }

        $this->cache[$key] = $value;

        return true;
    }

    public function delete($key)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function clear()
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function getMultiple($keys, $default = null)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function setMultiple($values, $ttl = null)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function deleteMultiple($keys)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function has($key)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }
}
