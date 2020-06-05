<?php

namespace Bugsnag\Tests\Cache\Adapter;

use Bugsnag\Cache\Adapter\CacheAdapterInterface;
use Bugsnag\Tests\TestCase;

/**
 * An abstract test class to ensure all adapters work identically.
 */
abstract class AdapterTest extends TestCase
{
    /**
     * @return CacheAdapterInterface
     */
    abstract protected function getAdapter();

    public function testAdapter()
    {
        $adapter = $this->getAdapter();

        $adapter->set('hello', 'world');
        $this->assertSame('world', $adapter->get('hello'));

        $adapter->set('key', 'value');
        $this->assertSame('value', $adapter->get('key'));

        $adapter->set('hello', 'goodbye');
        $this->assertSame('goodbye', $adapter->get('hello'));
        $this->assertSame('value', $adapter->get('key'));

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
}
