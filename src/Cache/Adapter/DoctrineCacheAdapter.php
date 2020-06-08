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
        try {
            // Reset a null TTL to our default. Doctrine shouldn't be fed null
            // values because it always expects an integer TTL and treats null
            // as if it were '0'. This isn't what we want because 0 means 'never
            // expire', so we reset it to our default instead.
            if (!is_int($ttl)) {
                $ttl = self::ONE_HOUR;
            }

            // Doctrine treats 0 to mean 'never expires', but this is counter
            // to how we define a TTL. We want a TTL of '0' to mean 'expire
            // immediately', so setting it to '-1' achieves this
            if ($ttl === 0) {
                $ttl = -1;
            }

            return $this->cache->save($key, $value, $ttl);
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }

        return false;
    }
}
