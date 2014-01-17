<?php

require_once 'Bugsnag_TestCase.php';

class StacktraceTest extends Bugsnag_TestCase
{
    protected $config;

    protected function setUp()
    {
        $this->config = new Bugsnag_Configuration();
    }

    public function testFromFrame()
    {
        $stacktrace = Bugsnag_Stacktrace::fromFrame($this->config, "some_file.php", 123)->toarray();

        $this->assertEquals(count($stacktrace), 1);
        $this->assertEquals($stacktrace[0]['file'], "some_file.php");
        $this->assertEquals($stacktrace[0]['lineNumber'], 123);
        $this->assertEquals($stacktrace[0]['method'], '[unknown]');
    }

    public function testFrameInsideBugsnag()
    {
        $frame = $this->getFixture('frames/non_bugsnag.json');
        $bugsnagFrame = $this->getFixture('frames/bugsnag.json');

        $this->assertEquals(Bugsnag_Stacktrace::frameInsideBugsnag($frame), false);
        $this->assertEquals(Bugsnag_Stacktrace::frameInsideBugsnag($bugsnagFrame), true);
    }

    public function testTriggeredErrorStacktrace()
    {
        $fixture = $this->getFixture('backtraces/trigger_error.json');
        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], $fixture['file'], $fixture['line'])->toArray();

        $this->assertEquals(count($stacktrace), 4);

        $this->assertEquals($stacktrace[0]["method"], "trigger_error");
        $this->assertEquals($stacktrace[0]["file"], "[internal]");
        $this->assertEquals($stacktrace[0]["lineNumber"], 0);

        $this->assertEquals($stacktrace[1]["method"], "crashy_function");
        $this->assertEquals($stacktrace[1]["file"], "/Users/james/src/bugsnag/bugsnag-php/testing.php");
        $this->assertEquals($stacktrace[1]["lineNumber"], 17);

        $this->assertEquals($stacktrace[2]["method"], "parent_of_crashy_function");
        $this->assertEquals($stacktrace[2]["file"], "/Users/james/src/bugsnag/bugsnag-php/testing.php");
        $this->assertEquals($stacktrace[2]["lineNumber"], 13);

        $this->assertEquals($stacktrace[3]["method"], "[main]");
        $this->assertEquals($stacktrace[3]["file"], "/Users/james/src/bugsnag/bugsnag-php/testing.php");
        $this->assertEquals($stacktrace[3]["lineNumber"], 20);
    }

    public function testErrorHandlerStacktrace()
    {
        $fixture = $this->getFixture('backtraces/error_handler.json');
        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], $fixture['file'], $fixture['line'])->toArray();

        $this->assertEquals(count($stacktrace), 3);

        $this->assertEquals($stacktrace[0]["method"], "crashy_function");
        $this->assertEquals($stacktrace[0]["file"], "/Users/james/src/bugsnag/bugsnag-php/testing.php");
        $this->assertEquals($stacktrace[0]["lineNumber"], 22);

        $this->assertEquals($stacktrace[1]["method"], "parent_of_crashy_function");
        $this->assertEquals($stacktrace[1]["file"], "/Users/james/src/bugsnag/bugsnag-php/testing.php");
        $this->assertEquals($stacktrace[1]["lineNumber"], 13);

        $this->assertEquals($stacktrace[2]["method"], "[main]");
        $this->assertEquals($stacktrace[2]["file"], "/Users/james/src/bugsnag/bugsnag-php/testing.php");
        $this->assertEquals($stacktrace[2]["lineNumber"], 25);
    }

    public function testExceptionHandlerStacktrace()
    {
        $fixture = $this->getFixture('backtraces/exception_handler.json');
        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], $fixture['file'], $fixture['line'])->toArray();

        $this->assertEquals(count($stacktrace), 3);

        $this->assertEquals($stacktrace[0]["method"], "crashy_function");
        $this->assertEquals($stacktrace[0]["file"], "/Users/james/src/bugsnag/bugsnag-php/testing.php");
        $this->assertEquals($stacktrace[0]["lineNumber"], 25);

        $this->assertEquals($stacktrace[1]["method"], "parent_of_crashy_function");
        $this->assertEquals($stacktrace[1]["file"], "/Users/james/src/bugsnag/bugsnag-php/testing.php");
        $this->assertEquals($stacktrace[1]["lineNumber"], 13);

        $this->assertEquals($stacktrace[2]["method"], "[main]");
        $this->assertEquals($stacktrace[2]["file"], "/Users/james/src/bugsnag/bugsnag-php/testing.php");
        $this->assertEquals($stacktrace[2]["lineNumber"], 28);
    }

    public function testAnonymousFunctionStackframes()
    {
        $fixture = $this->getFixture('backtraces/anonymous_frame.json');
        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], "somefile.php", 123);

        $this->assertEquals(count($stacktrace->toArray()), 5);
    }
}
