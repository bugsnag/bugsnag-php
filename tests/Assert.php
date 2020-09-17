<?php

namespace Bugsnag\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Assert as PhpUnitAssert;

/**
 * This class holds assertions that were removed/renamed/changed in various
 * PHPUnit versions, so that our test suite can be compatible with as many
 * versions as possible.
 */
final class Assert
{
    /**
     * A replacement for 'assertInternalType', which was removed in PHPUnit 9.
     *
     * @param string $type
     * @param mixed $value
     *
     * @return void
     */
    public static function isType($type, $value)
    {
        if (method_exists(PhpUnitAssert::class, 'assertInternalType')) {
            PhpUnitAssert::assertInternalType($type, $value);

            return;
        }

        $typeToAssertion = [
            'array' => [PhpUnitAssert::class, 'assertIsArray'],
            'bool' => [PhpUnitAssert::class, 'assertIsBool'],
            'callable' => [PhpUnitAssert::class, 'assertIsCallable'],
            'float' => [PhpUnitAssert::class, 'assertIsFloat'],
            'int' => [PhpUnitAssert::class, 'assertIsInt'],
            'iterable' => [PhpUnitAssert::class, 'assertIsIterable'],
            'numeric' => [PhpUnitAssert::class, 'assertIsNumeric'],
            'object' => [PhpUnitAssert::class, 'assertIsObject'],
            'resource' => [PhpUnitAssert::class, 'assertIsResource'],
            'scalar' => [PhpUnitAssert::class, 'assertIsScalar'],
            'string' => [PhpUnitAssert::class, 'assertIsString'],
        ];

        if (!isset($typeToAssertion[$type])) {
            throw new InvalidArgumentException("Unknown type '{$type}' given");
        }

        $typeToAssertion[$type]($value);
    }
}
