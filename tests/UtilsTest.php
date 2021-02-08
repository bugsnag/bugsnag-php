<?php

namespace Bugsnag\Tests;

use Bugsnag\Utils;

class UtilsTest extends TestCase
{
    /**
     * @dataProvider stringCaseEqualsProvider
     *
     * @param string $a
     * @param string $b
     * @param bool $expected
     * @param string|null $requiredVersion
     *
     * @return void
     */
    public function testStringCaseEquals($a, $b, $expected, $requiredVersion = null)
    {
        if ($requiredVersion !== null) {
            if (version_compare(PHP_VERSION, $requiredVersion, '<')) {
                $this->markTestSkipped("This test requires at least PHP {$requiredVersion} to run");
            }
        }

        $this->assertSame(
            $expected,
            Utils::stringCaseEquals($a, $b),
            sprintf(
                'Expected "%s" %s "%s"',
                $a,
                $expected ? 'to equal' : 'not to equal',
                $b
            )
        );

        $this->assertSame(
            $expected,
            Utils::stringCaseEquals($b, $a),
            sprintf(
                'Expected "%s" %s "%s"',
                $b,
                $expected ? 'to equal' : 'not to equal',
                $a
            )
        );
    }

    public function stringCaseEqualsProvider()
    {
        yield ['a', 'a', true];
        yield ['a', 'A', true];
        yield ['A', 'A', true];

        yield ['a', 'b', false];
        yield ['c', 'b', false];

        yield ['jalapeño', 'jalapeño', true];
        yield ['JALAPEÑO', 'jalapeño', true];
        yield ['JaLaPeÑo', 'jAlApEñO', true];
        yield ['jalapeño', 'jalapeno', false];

        // 6e cc 83 is equivalent to "\u{006E}\u{0303}" but in a way PHP 5 can
        // understand. This is the character "ñ" built out of "n" and a
        // combining tilde
        yield ["jalape\x6e\xcc\x83o", "jalape\x6e\xcc\x83o", true];
        yield ["jalape\x6e\xcc\x83o", 'jalapeño', true];

        // 4e cc 83 is equivalent to "\u{004E}\u{0303}", which is the capital
        // version of the above ("N" + a combining tilde)
        yield ["jalape\x6e\xcc\x83o", "jalape\x4e\xcc\x83o", true];

        // This is "ñ" both as a single character and with the combining tilde
        yield ["jalape\x6e\xcc\x83o", "jalape\xc3\xb1o", true];

        // This is "Ñ" as a single character and "ñ" with the combining tilde
        yield ["jalape\x6e\xcc\x83o", "jalape\xc3\x91o", true];

        yield ["jalape\x6e\xcc\x83o", 'jalapeno', false];

        // This test fails with a simple strcasecmp, proving that the MB string
        // functions are necessary in some cases
        // This requires PHP 7.3, which contains many MB String improvements:
        // https://www.php.net/manual/en/migration73.new-features.php#migration73.new-features.mbstring
        yield ['größer', 'gröẞer', true, '7.3.0'];
        yield ['größer', 'GRÖẞER', true, '7.3.0'];

        // Tests with characters from various unicode planes

        yield ['Iñtërnâtiônàližætiøn', 'Iñtërnâtiônàližætiøn', true];
        yield ['iñtërnâtiônàližætiøn', 'IÑTËRNÂTIÔNÀLIŽÆTIØN', true, '5.6.0'];

        yield ['обичам те', 'обичам те', true];
        yield ['обичам те', 'ОБИЧАМ ТЕ', true, '5.6.0'];
        yield ['ОбИчАм Те', 'оБиЧаМ тЕ', true, '5.6.0'];
        yield ['обичам те', 'oбичam te', false];

        yield ['大好きだよ', '大好きだよ', true];
        yield ['أحبك', 'أحبك', true];

        yield ['😀😀', '😀😀', true];

        yield ['👨‍👩‍👧‍👦🇬🇧', '👨‍👩‍👧‍👦🇬🇧', true];
        yield ['🇬🇧👨‍👩‍👧‍👦', '👨‍👩‍👧‍👦🇬🇧', false];

        $ukFlag = "\xf0\x9f\x87\xac\xf0\x9f\x87\xa7";
        yield ['👨‍👩‍👧‍👦'.$ukFlag, '👨‍👩‍👧‍👦🇬🇧', true];
        yield [$ukFlag.'👨‍👩‍👧‍👦', '👨‍👩‍👧‍👦🇬🇧', false];
    }
}
