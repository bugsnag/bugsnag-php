<?php

namespace Bugsnag\Tests\Cache\Adapter;

use Bugsnag\Cache\Adapter\CacheAdapterInterface;
use Bugsnag\Cache\CacheFactory;
use Bugsnag\Tests\TestCase;
use Doctrine\Common\Cache\Cache as DoctrineCache;
use Psr\Cache\CacheItemPoolInterface as Psr6CacheInterface;
use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;
use stdClass;

/**
 * An abstract test class to ensure all adapters work identically.
 */
abstract class AdapterTest extends TestCase
{
    /**
     * @return CacheAdapterInterface
     */
    abstract protected function getAdapter();

    /**
     * Get an implementation of the cache that the Adapter adapts. This lets us
     * pass it into the CacheFactory and test it works identically to when
     * instantiated manually.
     *
     * @return Psr16CacheInterface|Psr6CacheInterface|DoctrineCache
     */
    abstract protected function getImplementation();

    /**
     * @param CacheAdapterInterface $adapter
     *
     * @return void
     *
     * @dataProvider adapterProvider
     */
    final public function testAdapter(CacheAdapterInterface $adapter)
    {
        $this->assertTrue($adapter->set('hello', 'world'));
        $this->assertSame('world', $adapter->get('hello'));

        $this->assertTrue($adapter->set('key', 'value'));
        $this->assertSame('value', $adapter->get('key'));

        $this->assertTrue($adapter->set('hello', 'goodbye'));
        $this->assertSame('goodbye', $adapter->get('hello'));

        $this->assertTrue($adapter->set('HELLO', 'WORLD'));
        $this->assertSame('WORLD', $adapter->get('HELLO'));
        $this->assertSame('goodbye', $adapter->get('hello'));

        $this->assertTrue($adapter->set('key', false));
        $this->assertSame(false, $adapter->get('key'));

        $array = [1, 2, 'c', 'd', true, false];
        $this->assertTrue($adapter->set('key', $array));
        $this->assertSame($array, $adapter->get('key'));

        $object = new stdClass();
        $this->assertTrue($adapter->set('key', $object));
        $this->assertSame($object, $adapter->get('key'));

        $this->assertSame(null, $adapter->get('does not exist'));
        $this->assertSame(123, $adapter->get('also does not exist', 123));
        $this->assertSame(false, $adapter->get('does not exist too', false));
        $this->assertSame($this, $adapter->get('definitely does not exist', $this));

        $this->assertFalse($adapter->set([], 'abc'));
        $this->assertFalse($adapter->set($this, 'abc'));

        $this->assertSame(null, $adapter->get([]));
        $this->assertSame(false, $adapter->get([], false));
        $this->assertSame('hello', $adapter->get([], 'hello'));
        $this->assertSame(null, $adapter->get($this));
        $this->assertSame(false, $adapter->get($this, false));
        $this->assertSame('hello', $adapter->get($this, 'hello'));
    }

    /**
     * @param CacheAdapterInterface $adapter
     *
     * @return void
     *
     * @dataProvider ttlProvider
     */
    final public function testAdapterHonoursTimeToLive(
        CacheAdapterInterface $adapter,
        $ttl,
        $shouldBeInCache
    ) {
        $this->assertTrue($adapter->set('key', 'value', $ttl));

        $expected = $shouldBeInCache ? 'value' : null;
        $this->assertSame($expected, $adapter->get('key'));

        $expected = $shouldBeInCache ? 'value' : 'hello';
        $this->assertSame($expected, $adapter->get('key', 'hello'));
    }

    final public function adapterProvider()
    {
        $manuallyInstantiated = $this->getAdapter();

        $factory = new CacheFactory();
        $factoryInstantiated = $factory->create($this->getImplementation());

        // Sanity check we're doing the right thing in the concrete test class
        $this->assertInstanceOf(
            get_class($manuallyInstantiated),
            $factoryInstantiated
        );

        return [
            'manually instantiated' => [$manuallyInstantiated],
            'factory instantiated' => [$factoryInstantiated],
        ];
    }

    final public function ttlProvider()
    {
        $tests = [
            [100000, true],
            [100, true],
            [1, true],
            [null, true],
            [0, false],
            [-1, false],
            [-100, false],
            [-100000, false],
        ];

        foreach ($this->adapterProvider() as $adapterLabel => $adapter) {
            $adapter = $adapter[0];

            foreach ($tests as $test) {
                $ttl = $test[0];
                $shouldBeInCache = $test[1];
                $label = sprintf(
                    '(%s) ttl %s => %s',
                    $adapterLabel,
                    is_int($ttl) ? "{$ttl}" : 'null',
                    $shouldBeInCache ? 'should be in cache' : 'should be expired'
                );

                yield $label => [$adapter, $ttl, $shouldBeInCache];
            }
        }
    }
}
