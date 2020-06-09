<?php

namespace Bugsnag\Tests\Cache\Adapter;

use Bugsnag\Cache\Adapter\Psr6Adapter;
use Bugsnag\Tests\Fake\FakePsr6CacheItem;
use Bugsnag\Tests\Fake\FakePsr6CachePool;
use Psr\Cache\CacheItemPoolInterface;
use TypeError;

final class Psr6AdapterTest extends AdapterTest
{
    protected function getAdapter()
    {
        return new Psr6Adapter($this->getImplementation());
    }

    protected function getImplementation()
    {
        return new FakePsr6CachePool();
    }

    /**
     * This test requires 'TypeError' and 'Throwable' to exist, which is only
     * true on PHP 7+.
     *
     * This doesn't need to be tested on PHP 5 because if 'Throwable' doesn't
     * exist then it's not possible to throw or catch anything that's not an
     * 'Exception' anyway
     *
     * @requires PHP 7.0
     */
    public function testItHandlesErrorsNotJustExceptions()
    {
        /** @var CacheItemPoolInterface&\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();

        $calls = 0;
        $cache->expects($this->exactly(4))
            ->method('getItem')
            ->withAnyParameters()
            ->willReturnCallback(function ($key) use (&$calls) {
                $calls++;

                // On the first call we want 'getItem' to return something
                // rather than throw, because 'set' relies on getting a cache
                // item from the pool. This is kind of coupled to the adapter
                // implementation, but it's how the interface is supposed to
                // work so I think it's fine (if a bit ugly)
                if ($calls === 1) {
                    return new FakePsr6CacheItem($key);
                }

                throw new TypeError('Oh no!');
            });

        $cache->expects($this->once())
            ->method('save')
            ->willReturnCallback(function () {
                throw new TypeError('oh no!');
            });

        $adapter = new Psr6Adapter($cache);

        $this->assertFalse($adapter->set('test', 1));
        $this->assertSame(null, $adapter->get('test'));
        $this->assertSame(true, $adapter->get('test', true));
        $this->assertSame('abc', $adapter->get('test', 'abc'));
    }
}
