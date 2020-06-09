<?php

namespace Bugsnag\Cache\Adapter;

use Doctrine\Common\Cache\Cache as DoctrineCache;
use Exception;
use Throwable;

final class DoctrineCacheAdapter implements CacheAdapterInterface
{
    /**
     * @var DoctrineCache
     */
    private $cache;

    public function __construct(DoctrineCache $cache)
    {
        $this->cache = $cache;
    }

    public function get($key, $default = null)
    {
        try {
            $value = $this->cache->fetch($key);

            if ($value === false) {
                // Doctrine Cache specifies returning false if a key is not
                // found. However, 'false' is a possible value to cache so we
                // need to make sure the key doesn't exist
                // TODO we probably don't need to store 'false' in the cache so
                //      can probably get away without doing this — this would
                //      save a second round trip to the cache
                if ($this->cache->contains($key)) {
                    return false;
                }

                return $default;
            }

            return $value;
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }

        return $default;
    }

    public function set($key, $value, $ttl = self::ONE_HOUR)
    {
        // If we got a TTL less than or equal to 0, don't cache anything but
        // pretend we did. Doctrine cache implementations act differently when
        // given a TTL below 0 — some will store the TTL and work as expected,
        // others will ignore it and never expire the entry.
        if (is_int($ttl) && $ttl <= 0) {
            return true;
        }

        try {
            // Reset a null TTL to our default. Doctrine shouldn't be fed null
            // values because it always expects an integer TTL and treats null
            // as if it were '0'. This isn't what we want because 0 means 'never
            // expire', so we reset it to our default instead.
            if (!is_int($ttl)) {
                $ttl = self::ONE_HOUR;
            }

            return $this->cache->save($key, $value, $ttl);
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }

        return false;
    }
}
