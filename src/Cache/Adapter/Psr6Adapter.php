<?php

namespace Bugsnag\Cache\Adapter;

use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Throwable;

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
        try {
            $item = $this->cache->getItem($key);

            if ($item->isHit()) {
                return $item->get();
            }
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }

        return $default;
    }

    public function set($key, $value)
    {
        try {
            $item = $this->cache->getItem($key);
            $item->set($value);

            return $this->cache->save($item);
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }

        return false;
    }
}
