<?php

namespace Bugsnag\Tests\Cache;

use Bugsnag\Cache\Adapter\CacheAdapterInterface;
use Bugsnag\Cache\CacheFactory;
use Bugsnag\Tests\Fake\FakePsr16Cache;
use Bugsnag\Tests\TestCase;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;
use stdClass;

final class CacheFactoryTest extends TestCase
{
    public function testItCreatesACacheAdapterForPsr16()
    {
        $factory = new CacheFactory();
        $adapter = $factory->create(new FakePsr16Cache());

        $this->assertInstanceOf(CacheAdapterInterface::class, $adapter);
    }

    /**
     * @param string $type
     * @param mixed  $cache
     *
     * @return void
     *
     * @dataProvider invalidTypeProvider
     */
    public function testItThrowsWhenGivenAnInvalidType($type, $cache)
    {
        $factory = new CacheFactory();

        $this->expectedException(
            InvalidArgumentException::class,
            sprintf(
                '%s::create expects an instance of "%s", got "%s"',
                CacheFactory::class,
                Psr16CacheInterface::class,
                $type
            )
        );

        $factory->create($cache);
    }

    public function invalidTypeProvider()
    {
        $tests = [
            ['NULL', null],
            ['string', 'hello'],
            ['integer', 123],
            ['boolean', false],
            ['array', ['hello', 123, false]],
            [stdClass::class, new stdClass()],
            [self::class, $this],
        ];

        foreach ($tests as $test) {
            yield $test[0] => $test;
        }
    }
}
