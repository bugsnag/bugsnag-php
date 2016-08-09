<?php

namespace Bugsnag\Tests;

use Bugsnag\Parser;
use Generator;
use PHPUnit_Framework_TestCase as TestCase;

class ParserTest extends TestCase
{
    public function testCanParse()
    {
        $parser = new Parser();

        $expected = [
            [
                'token' => 'T_OPEN_TAG',
                'content' => "<?php\n",
                'line' => 1,
            ],
            [
                'token' => 'T_ECHO',
                'content' => 'echo',
                'line' => 2,
            ],
            [
                'token' => 'T_WHITESPACE',
                'content' => ' ',
                'line' => 2,
            ],
            [
                'token' => 'T_CONSTANT_ENCAPSED_STRING',
                'content' => '\'foo\'',
                'line' => 2,
            ],
            [
                'token' => 'T_OTHER',
                'content' => ';',
                'line' => 2,
            ],
            [
                'token' => 'T_WHITESPACE',
                'content' => "\n",
                'line' => 2,
            ],
            [
                'token' => 'T_COMMENT',
                'content' => '// hi',
                'line' => 3,
            ],
        ];

        $parsed = $parser->parse("<?php\necho 'foo';\n// hi", 2);

        $this->assertSame($expected, $parsed);
    }

    public function testWithRestrictedStart()
    {
        $parser = new Parser();

        $expected = [
            [
                'token' => 'T_WHITESPACE',
                'content' => "\n",
                'line' => 2,
            ],
            [
                'token' => 'T_COMMENT',
                'content' => '// hi',
                'line' => 3,
            ],
        ];

        $parsed = $parser->parse("<?php\necho 'foo';\n// hi", 3, 1);

        $this->assertSame($expected, $parsed);
    }

    public function testWithLongStart()
    {
        $parser = new Parser();

        $expected = [
            [
                'token' => 'T_OPEN_TAG',
                'content' => "<?php\n",
                'line' => 1,
            ],
            [
                'token' => 'T_ECHO',
                'content' => 'echo',
                'line' => 2,
            ],
            [
                'token' => 'T_WHITESPACE',
                'content' => ' ',
                'line' => 2,
            ],
            [
                'token' => 'T_CONSTANT_ENCAPSED_STRING',
                'content' => '\'foo\'',
                'line' => 2,
            ],
            [
                'token' => 'T_OTHER',
                'content' => ';',
                'line' => 2,
            ],
            [
                'token' => 'T_WHITESPACE',
                'content' => "\n",
                'line' => 2,
            ],
            [
                'token' => 'T_COMMENT',
                'content' => '// hi',
                'line' => 3,
            ],
        ];

        $parsed = $parser->parse("<?php\necho 'foo';\n// hi", 2, 100);

        $this->assertSame($expected, $parsed);
    }

    public function testWithRestrictedEnd()
    {
        $parser = new Parser();

        $expected = [
            [
                'token' => 'T_OPEN_TAG',
                'content' => "<?php\n",
                'line' => 1,
            ],
            [
                'token' => 'T_ECHO',
                'content' => 'echo',
                'line' => 2,
            ],
            [
                'token' => 'T_WHITESPACE',
                'content' => ' ',
                'line' => 2,
            ],
            [
                'token' => 'T_CONSTANT_ENCAPSED_STRING',
                'content' => '\'foo\'',
                'line' => 2,
            ],
            [
                'token' => 'T_OTHER',
                'content' => ';',
                'line' => 2,
            ],
        ];

        $parsed = $parser->parse("<?php\necho 'foo';\n// hi", 2, 1, 3);

        $this->assertSame($expected, $parsed);
    }

    public function testWithLongEnd()
    {
        $parser = new Parser();

        $expected = [
            [
                'token' => 'T_OPEN_TAG',
                'content' => "<?php\n",
                'line' => 1,
            ],
            [
                'token' => 'T_ECHO',
                'content' => 'echo',
                'line' => 2,
            ],
            [
                'token' => 'T_WHITESPACE',
                'content' => ' ',
                'line' => 2,
            ],
            [
                'token' => 'T_CONSTANT_ENCAPSED_STRING',
                'content' => '\'foo\'',
                'line' => 2,
            ],
            [
                'token' => 'T_OTHER',
                'content' => ';',
                'line' => 2,
            ],
            [
                'token' => 'T_WHITESPACE',
                'content' => "\n",
                'line' => 2,
            ],
            [
                'token' => 'T_COMMENT',
                'content' => '// hi',
                'line' => 3,
            ],
        ];

        $parsed = $parser->parse("<?php\necho 'foo';\n// hi", 2, 1, 100);

        $this->assertSame($expected, $parsed);
    }
}
