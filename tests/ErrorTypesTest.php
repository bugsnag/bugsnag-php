<?php

namespace Bugsnag\Tests;

use Bugsnag\ErrorTypes;

class ErrorTypesTest extends TestCase
{
    /**
     * @dataProvider levelsForSeverityProvider
     *
     * @param string $severity
     * @param int $expected
     *
     * @return void
     */
    public function testGetLevelsForSeverity($severity, $expected)
    {
        $this->assertSame($expected, ErrorTypes::getLevelsForSeverity($severity));
    }

    /**
     * @dataProvider isFatalProvider
     *
     * @param int $code
     * @param bool $expected
     *
     * @return void
     */
    public function testIsFatal($code, $expected)
    {
        $this->assertSame($expected, ErrorTypes::isFatal($code));
    }

    /**
     * @dataProvider nameProvider
     *
     * @param int $code
     * @param string $expected
     *
     * @return void
     */
    public function testGetName($code, $expected)
    {
        $this->assertSame($expected, ErrorTypes::getName($code));
    }

    /**
     * @dataProvider severityProvider
     *
     * @param int $code
     * @param string $expected
     *
     * @return void
     */
    public function testGetSeverity($code, $expected)
    {
        $this->assertSame($expected, ErrorTypes::getSeverity($code));
    }

    public function testGetAllCodes()
    {
        $codes = ErrorTypes::getAllCodes();

        if (PHP_VERSION_ID < 80400)
            unset($codes[E_STRICT]);

        // If we actually got all of the codes, they should combine to equal E_ALL
        $combined = array_reduce($codes, function ($acc, $code) {
            return $acc | $code;
        }, 0);

        $this->assertSame(E_ALL, $combined);
    }

    /**
     * @dataProvider codeToStringProvider
     *
     * @param int $code
     * @param string $expected
     *
     * @return void
     */
    public function testCodeToString($code, $expected)
    {
        $this->assertSame($expected, ErrorTypes::codeToString($code));
    }

    public function levelsForSeverityProvider()
    {
        $levels = [
            'error' => [
                'error',
                E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR,
            ],
            'warning' => [
                'warning',
                E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING,
            ],
            'info' => [
                'info',
                E_NOTICE | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED,
            ],
            'non existent severity' => [
                'non existent severity',
                0,
            ],
        ];

        if (PHP_VERSION_ID < 80400) {
            $levels['info'][1] |= E_STRICT;
        }

        return $levels;
    }

    public function isFatalProvider()
    {
        $fatals = [
            'E_ERROR' => [E_ERROR, true],
            'E_PARSE' => [E_PARSE, true],
            'E_CORE_ERROR' => [E_CORE_ERROR, true],
            'E_COMPILE_ERROR' => [E_COMPILE_ERROR, true],
            'E_USER_ERROR' => [E_USER_ERROR, true],
            'E_RECOVERABLE_ERROR' => [E_RECOVERABLE_ERROR, true],
            'E_WARNING' => [E_WARNING, false],
            'E_CORE_WARNING' => [E_CORE_WARNING, false],
            'E_COMPILE_WARNING' => [E_COMPILE_WARNING, false],
            'E_USER_WARNING' => [E_USER_WARNING, false],
            'E_NOTICE' => [E_NOTICE, false],
            'E_USER_NOTICE' => [E_USER_NOTICE, false],
            'E_DEPRECATED' => [E_DEPRECATED, false],
            'E_USER_DEPRECATED' => [E_USER_DEPRECATED, false],
            'invalid code' => ['hello', true],
        ];

        if (PHP_VERSION_ID < 80400) {
            $fatals['E_STRICT'] = [E_STRICT, false];
        }

        return $fatals;
    }

    public function nameProvider()
    {
        $names = [
            'E_ERROR' => [E_ERROR, 'PHP Fatal Error'],
            'E_WARNING' => [E_WARNING, 'PHP Warning'],
            'E_PARSE' => [E_PARSE, 'PHP Parse Error'],
            'E_NOTICE' => [E_NOTICE, 'PHP Notice'],
            'E_CORE_ERROR' => [E_CORE_ERROR, 'PHP Core Error'],
            'E_CORE_WARNING' => [E_CORE_WARNING, 'PHP Core Warning'],
            'E_COMPILE_ERROR' => [E_COMPILE_ERROR, 'PHP Compile Error'],
            'E_COMPILE_WARNING' => [E_COMPILE_WARNING, 'PHP Compile Warning'],
            'E_USER_ERROR' => [E_USER_ERROR, 'User Error'],
            'E_USER_WARNING' => [E_USER_WARNING, 'User Warning'],
            'E_USER_NOTICE' => [E_USER_NOTICE, 'User Notice'],
            'E_RECOVERABLE_ERROR' => [E_RECOVERABLE_ERROR, 'PHP Recoverable Error'],
            'E_DEPRECATED' => [E_DEPRECATED, 'PHP Deprecated'],
            'E_USER_DEPRECATED' => [E_USER_DEPRECATED, 'User Deprecated'],
            'invalid code' => ['hello', 'Unknown'],
        ];

        if (PHP_VERSION_ID < 80400) {
            $names['E_STRICT'] = [E_STRICT, 'PHP Strict'];
        }

        return $names;
    }

    public function severityProvider()
    {
        $severities = [
            'E_ERROR' => [E_ERROR, 'error'],
            'E_PARSE' => [E_PARSE, 'error'],
            'E_CORE_ERROR' => [E_CORE_ERROR, 'error'],
            'E_COMPILE_ERROR' => [E_COMPILE_ERROR, 'error'],
            'E_USER_ERROR' => [E_USER_ERROR, 'error'],
            'E_RECOVERABLE_ERROR' => [E_RECOVERABLE_ERROR, 'error'],
            'E_WARNING' => [E_WARNING, 'warning'],
            'E_CORE_WARNING' => [E_CORE_WARNING, 'warning'],
            'E_COMPILE_WARNING' => [E_COMPILE_WARNING, 'warning'],
            'E_USER_WARNING' => [E_USER_WARNING, 'warning'],
            'E_NOTICE' => [E_NOTICE, 'info'],
            'E_USER_NOTICE' => [E_USER_NOTICE, 'info'],
            'E_DEPRECATED' => [E_DEPRECATED, 'info'],
            'E_USER_DEPRECATED' => [E_USER_DEPRECATED, 'info'],
            'invalid code' => ['hello', 'error'],
        ];

        if (PHP_VERSION_ID < 80400) {
            $severities['E_STRICT'] = [E_STRICT, 'info'];
        }

        return $severities;
    }

    public function codeToStringProvider()
    {
        $strings = [
            'E_ERROR' => [E_ERROR, 'E_ERROR'],
            'E_PARSE' => [E_PARSE, 'E_PARSE'],
            'E_CORE_ERROR' => [E_CORE_ERROR, 'E_CORE_ERROR'],
            'E_COMPILE_ERROR' => [E_COMPILE_ERROR, 'E_COMPILE_ERROR'],
            'E_USER_ERROR' => [E_USER_ERROR, 'E_USER_ERROR'],
            'E_RECOVERABLE_ERROR' => [E_RECOVERABLE_ERROR, 'E_RECOVERABLE_ERROR'],
            'E_WARNING' => [E_WARNING, 'E_WARNING'],
            'E_CORE_WARNING' => [E_CORE_WARNING, 'E_CORE_WARNING'],
            'E_COMPILE_WARNING' => [E_COMPILE_WARNING, 'E_COMPILE_WARNING'],
            'E_USER_WARNING' => [E_USER_WARNING, 'E_USER_WARNING'],
            'E_NOTICE' => [E_NOTICE, 'E_NOTICE'],
            'E_USER_NOTICE' => [E_USER_NOTICE, 'E_USER_NOTICE'],
            'E_DEPRECATED' => [E_DEPRECATED, 'E_DEPRECATED'],
            'E_USER_DEPRECATED' => [E_USER_DEPRECATED, 'E_USER_DEPRECATED'],
            'invalid code' => ['hello', 'Unknown'],
        ];

        if (PHP_VERSION_ID < 80400) {
            $strings['E_STRICT'] = [E_STRICT, 'E_STRICT'];
        }

        return $strings;
    }
}
