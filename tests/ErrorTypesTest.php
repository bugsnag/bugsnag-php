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
}
