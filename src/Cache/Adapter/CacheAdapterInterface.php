<?php

namespace Bugsnag\Cache\Adapter;

interface CacheAdapterInterface
{
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
     *
     * @return bool
     */
    public function set($key, $value);
}
