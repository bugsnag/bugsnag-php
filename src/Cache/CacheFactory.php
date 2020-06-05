<?php

namespace Bugsnag\Cache;

use Bugsnag\Cache\Adapter\CacheAdapterInterface;
use Bugsnag\Cache\Adapter\Psr16Adapter;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;

final class CacheFactory
{
    /**
     * @param Psr16CacheInterface $cache
     *
     * @return CacheAdapterInterface
     */
    public function create($cache)
    {
        if (!$cache instanceof Psr16CacheInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s::%s expects an instance of "%s", got "%s"',
                    self::class,
                    __METHOD__,
                    Psr16CacheInterface::class,
                    is_object($cache) ? get_class($cache) : gettype($cache)
                )
            );
        }

        return new Psr16Adapter($cache);
    }
}
