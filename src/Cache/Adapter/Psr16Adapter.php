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
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return $default;
    }

    public function set($key, $value)
    {
        try {
            return $this->cache->set($key, $value);
        } catch (Throwable $e) {
        } catch (Exception $e) {
        }

        return false;
    }
}
