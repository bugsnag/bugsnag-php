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

    public function set($key, $value, $ttl = self::ONE_HOUR)
    {
        try {
            $item = $this->cache->getItem($key);
            $item->set($value);

            // Reset a null TTL to our default. There is no specification for
            // what libraries should do when given null, so we can't be sure
            // that forwarding it on is OK. Instead we sidestep the issue by
            // using a sensible default
            if (!is_int($ttl)) {
                $ttl = self::ONE_HOUR;
            }

            $item->expiresAfter($ttl);

            return $this->cache->save($item);
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }

        return false;
    }
}
