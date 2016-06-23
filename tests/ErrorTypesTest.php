<?php

namespace Bugsnag\Tests;

use Bugsnag\ErrorTypes;

class ErrorTypesTest extends AbstractTestCase
{
    public function testGetLevelsForSeverity()
    {
        $this->assertEquals(ErrorTypes::getLevelsForSeverity('error'), 4437);
        $this->assertEquals(ErrorTypes::getLevelsForSeverity('warning'), 674);
        $this->assertEquals(ErrorTypes::getLevelsForSeverity('info'), 27656);
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
