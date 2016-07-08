<?php

namespace Bugsnag\Tests;

use Bugsnag\Parser;
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

        $this->assertSame($expected, $parser->parse("<?php\necho 'foo';\n// hi"));
    }

    public function testWithRestrictedStart()
    {
        $parser = new Parser();

        $expected = [
            [
                'token' => 'T_COMMENT',
                'content' => '// hi',
                'line' => 3,
            ],
        ];

        $this->assertSame($expected, $parser->parse("<?php\necho 'foo';\n// hi", 3));
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
            [
                'token' => 'T_WHITESPACE',
                'content' => "\n",
                'line' => 2,
            ],
        ];

        $this->assertSame($expected, $parser->parse("<?php\necho 'foo';\n// hi", 1, 2));
    }
}
