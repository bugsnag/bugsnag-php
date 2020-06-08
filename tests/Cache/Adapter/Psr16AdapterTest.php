<?php

namespace Bugsnag\Tests\Cache\Adapter;

use Bugsnag\Cache\Adapter\Psr16Adapter;
use Bugsnag\Tests\Fake\FakePsr16Cache;
use Psr\SimpleCache\CacheInterface;
use TypeError;

final class Psr16AdapterTest extends AdapterTest
{
    protected function getAdapter()
    {
        return new Psr16Adapter($this->getImplementation());
    }

    protected function getImplementation()
    {
        return new FakePsr16Cache();
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
        /** @var CacheInterface&\PHPUnit\Framework\MockObject\MockObject $cache */
        $cache = $this->getMockBuilder(CacheInterface::class)->getMock();

        $this->willThrow($cache, $this->exactly(3), 'get', new TypeError('no'));
        $this->willThrow($cache, $this->once(), 'set', new TypeError('no'));

        $adapter = new Psr16Adapter($cache);

        $this->assertFalse($adapter->set('test', 1));
        $this->assertSame(null, $adapter->get('test'));
        $this->assertSame(true, $adapter->get('test', true));
        $this->assertSame('abc', $adapter->get('test', 'abc'));
    }
}
