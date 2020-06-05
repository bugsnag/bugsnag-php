<?php

namespace Bugsnag\Cache\Adapter;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

final class Psr16Adapter implements CacheAdapterInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get($key, $default = null)
    {
        try {
            return $this->cache->get($key, $default);
        } catch (InvalidArgumentException $e) {
            return $default;
        }
    }

    public function set($key, $value)
    {
        try {
            return $this->cache->set($key, $value);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}
