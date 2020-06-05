<?php

namespace Bugsnag\Tests\Cache\Adapter;

use Bugsnag\Cache\Adapter\Psr6Adapter;
use Bugsnag\Tests\Fake\FakePsr6CachePool;

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
}
