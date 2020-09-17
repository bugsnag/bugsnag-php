<?php

namespace Bugsnag\Tests\Fakes;

final class StringableObject
{
    public function __toString()
    {
        return '2object2string';
    }
}
