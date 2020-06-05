<?php

namespace Bugsnag\Tests\Cache\Adapter;

use Bugsnag\Cache\Adapter\DoctrineCacheAdapter;
use Doctrine\Common\Cache\ArrayCache;

final class DoctrineCacheAdapterTest extends AdapterTest
{
    protected function getAdapter()
    {
        return new DoctrineCacheAdapter($this->getImplementation());
    }

    protected function getImplementation()
    {
        return new ArrayCache();
    }
}
