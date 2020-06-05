<?php

namespace Bugsnag\Cache\Adapter;

use Psr\Cache\CacheItemPoolInterface;

final class Psr6Adapter implements CacheAdapterInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get($key, $default = null)
    {
        $item = $this->cache->getItem($key);

        if ($item->isHit()) {
            return $item->get();
        }

        return $default;
    }

    public function set($key, $value)
    {
        $item = $this->cache->getItem($key);
        $item->set($value);

        return $this->cache->save($item);
    }
}
