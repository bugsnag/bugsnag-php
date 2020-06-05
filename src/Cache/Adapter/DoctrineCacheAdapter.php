<?php

namespace Bugsnag\Cache\Adapter;

use Doctrine\Common\Cache\Cache as DoctrineCache;

final class DoctrineCacheAdapter implements CacheAdapterInterface
{
    /**
     * @var DoctrineCache
     */
    private $cache;

    public function __construct(DoctrineCache $cache)
    {
        $this->cache = $cache;
    }

    public function get($key, $default = null)
    {
        $value = $this->cache->fetch($key);

        if ($value === false) {
            // Doctrine Cache specifies returning false if a key is not found.
            // However, 'false' is a possible value to cache so we need to make
            // sure the key doesn't exist
            // TODO we probably don't need to store 'false' in the cache so can
            //      probably get away without doing this — this would save a
            //      second round trip to the cache
            if ($this->cache->contains($key)) {
                return false;
            }

            return $default;
        }

        return $value;
    }

    public function set($key, $value)
    {
        return $this->cache->save($key, $value);
    }
}
