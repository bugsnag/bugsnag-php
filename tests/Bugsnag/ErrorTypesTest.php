<?php

class ErrorTypesTest extends PHPUnit_Framework_TestCase
{
    public function testGetLevelsForSeverity()
    {
        $this->assertSame(Bugsnag_ErrorTypes::getLevelsForSeverity('error'), 4437);
        $this->assertSame(Bugsnag_ErrorTypes::getLevelsForSeverity('warning'), 674);
        $this->assertSame(Bugsnag_ErrorTypes::getLevelsForSeverity('info'), 27656);
    }

    public function testIsFatal()
    {
        $this->assertFalse(Bugsnag_ErrorTypes::isFatal(E_CORE_WARNING));
        $this->assertTrue(Bugsnag_ErrorTypes::isFatal(E_COMPILE_ERROR));
    }

    public function testGetName()
    {
        $this->assertSame('PHP Notice', Bugsnag_ErrorTypes::getName(E_NOTICE));
        $this->assertSame('Unknown', Bugsnag_ErrorTypes::getName(42));
    }

    public function testGetSeverity()
    {
        $this->assertSame('info', Bugsnag_ErrorTypes::getSeverity(E_NOTICE));
        $this->assertSame('error', Bugsnag_ErrorTypes::getSeverity(42));
    }
}
