<?php

namespace Bugsnag\Cache\Adapter;

use Exception;
use Psr\SimpleCache\CacheInterface;
use Throwable;

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
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }

        return $default;
    }

    public function set($key, $value, $ttl = self::ONE_HOUR)
    {
        try {
            // Reset a null TTL to our default. There is no specification for
            // what libraries should do when given null, so we can't be sure
            // that forwarding it on is OK. Instead we sidestep the issue by
            // using a sensible default
            if (!is_int($ttl)) {
                $ttl = self::ONE_HOUR;
            }

            return $this->cache->set($key, $value, $ttl);
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }

        return false;
    }
}
