<?php

namespace Bugsnag\Tests\Cache\Adapter;

use Bugsnag\Cache\Adapter\Psr16Adapter;
use Bugsnag\Tests\Fake\FakePsr16Cache;

final class Psr16AdapterTest extends AdapterTest
{
    protected function getAdapter()
    {
        return new Psr16Adapter(new FakePsr16Cache());
    }
}
