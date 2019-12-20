<?php

namespace Bugsnag\Tests;

use Bugsnag\ErrorTypes;

class ErrorTypesTest extends TestCase
{
    public function testGetLevelsForSeverity()
    {
        $this->assertSame(4437, ErrorTypes::getLevelsForSeverity('error'));
        $this->assertSame(674, ErrorTypes::getLevelsForSeverity('warning'));
        $this->assertSame(27656, ErrorTypes::getLevelsForSeverity('info'));
    }

    public function testIsFatal()
    {
        $this->assertFalse(ErrorTypes::isFatal(E_CORE_WARNING));
        $this->assertTrue(ErrorTypes::isFatal(E_COMPILE_ERROR));
    }

    public function testGetName()
    {
        $this->assertSame('PHP Notice', ErrorTypes::getName(E_NOTICE));
        $this->assertSame('Unknown', ErrorTypes::getName(42));
    }

    public function testGetSeverity()
    {
        $this->assertSame('info', ErrorTypes::getSeverity(E_NOTICE));
        $this->assertSame('error', ErrorTypes::getSeverity(42));
    }
}
