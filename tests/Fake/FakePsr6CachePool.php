<?php

namespace Bugsnag\Tests\Fake;

use BadMethodCallException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * A fake PSR-6 cache item pool implementation for use in unit tests.
 *
 * This implements the bare minimum methods that we need and will throw an
 * exception if any upimplemented method is called.
 */
final class FakePsr6CachePool implements CacheItemPoolInterface
{
    private $cache = [];

    public function getItem($key)
    {
        if (!is_string($key)) {
            throw new FakePsr6Exception('Invalid key given');
        }

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        return new FakePsr6CacheItem($key);
    }

    public function save(CacheItemInterface $item)
    {
        if (!$item instanceof FakePsr6CacheItem) {
            throw new FakePsr6Exception('Invalid item given: '.get_class($item));
        }

        $this->cache[$item->getKey()] = $item->save();

        return true;
    }

    public function getItems(array $keys = [])
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function hasItem($key)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function clear()
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function deleteItem($key)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function deleteItems(array $keys)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function saveDeferred(CacheItemInterface $item)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }

    public function commit()
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented');
    }
}
