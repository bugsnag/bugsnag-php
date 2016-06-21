<?php

class ErrorTypesTest extends PHPUnit_Framework_TestCase
{
    public function testGetLevelsForSeverity()
    {
        $this->assertSame(Bugsnag_ErrorTypes::getLevelsForSeverity('error'), 4437);
        $this->assertSame(Bugsnag_ErrorTypes::getLevelsForSeverity('warning'), 674);
        $this->assertSame(Bugsnag_ErrorTypes::getLevelsForSeverity('info'), 27656);
    }
}
