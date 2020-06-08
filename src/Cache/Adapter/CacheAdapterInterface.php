<?php

namespace Bugsnag\Cache\Adapter;

interface CacheAdapterInterface
{
    /**
     * One hour in seconds (60 * 60 * 24).
     */
    const ONE_HOUR = 86400;

    /**
     * Fetches a value from the cache.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed The cached item, or $default if the key was not found
     */
    public function get($key, $default = null);

    /**
     * Persists a value to the cache.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl   How long this entry should be stored, in seconds.
     *                      '0' is not treated as special, instead it means the
     *                      value expires in 0 seconds (i.e. immediately).
     *
     * @return bool
     */
    public function set($key, $value, $ttl = self::ONE_HOUR);
}
