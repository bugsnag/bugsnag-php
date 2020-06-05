<?php

namespace Bugsnag\Cache;

use Bugsnag\Cache\Adapter\CacheAdapterInterface;
use Bugsnag\Cache\Adapter\Psr16Adapter;
use Bugsnag\Cache\Adapter\Psr6Adapter;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface as Psr6CacheInterface;
use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;

final class CacheFactory
{
    /**
     * @param Psr16CacheInterface|Psr6CacheInterface $cache
     *
     * @return CacheAdapterInterface
     */
    public function create($cache)
    {
        if ($cache instanceof Psr16CacheInterface) {
            return new Psr16Adapter($cache);
        }

        if ($cache instanceof Psr6CacheInterface) {
            return new Psr6Adapter($cache);
        }

        throw new InvalidArgumentException(
            sprintf(
                '%s::%s expects an instance of "%s" or "%s", got "%s"',
                self::class,
                __METHOD__,
                Psr6CacheInterface::class,
                Psr16CacheInterface::class,
                is_object($cache) ? get_class($cache) : gettype($cache)
            )
        );
    }
}
