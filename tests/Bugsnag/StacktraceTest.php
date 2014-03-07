<?php

require_once 'Bugsnag_TestCase.php';

class StacktraceTest extends Bugsnag_TestCase
{
    protected $config;

    protected function setUp()
    {
        $this->config = new Bugsnag_Configuration();
    }

    protected function assertFrameEquals($frame, $method, $file, $line)
    {
        $this->assertEquals($frame["method"], $method);
        $this->assertEquals($frame["file"], $file);
        $this->assertEquals($frame["lineNumber"], $line);
    }

    public function testFromFrame()
    {
        $stacktrace = Bugsnag_Stacktrace::fromFrame($this->config, "some_file.php", 123)->toarray();

        $this->assertCount(1, $stacktrace);
        $this->assertFrameEquals($stacktrace[0], "[unknown]", "some_file.php", 123);
    }

    public function testFrameInsideBugsnag()
    {
        $frame = $this->getFixture('frames/non_bugsnag.json');
        $bugsnagFrame = $this->getFixture('frames/bugsnag.json');

        $this->assertFalse(Bugsnag_Stacktrace::frameInsideBugsnag($frame));
        $this->assertTrue(Bugsnag_Stacktrace::frameInsideBugsnag($bugsnagFrame));
    }

    public function testTriggeredErrorStacktrace()
    {
        $fixture = $this->getFixture('backtraces/trigger_error.json');
        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], $fixture['file'], $fixture['line'])->toArray();

        $this->assertCount(4, $stacktrace);

        $this->assertFrameEquals($stacktrace[0], "trigger_error", "[internal]", 0);
        $this->assertFrameEquals($stacktrace[1], "crashy_function", "/Users/james/src/bugsnag/bugsnag-php/testing.php", 17);
        $this->assertFrameEquals($stacktrace[2], "parent_of_crashy_function", "/Users/james/src/bugsnag/bugsnag-php/testing.php", 13);
        $this->assertFrameEquals($stacktrace[3], "[main]", "/Users/james/src/bugsnag/bugsnag-php/testing.php", 20);
    }

    public function testErrorHandlerStacktrace()
    {
        $fixture = $this->getFixture('backtraces/error_handler.json');
        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], $fixture['file'], $fixture['line'])->toArray();

        $this->assertCount(3, $stacktrace);

        $this->assertFrameEquals($stacktrace[0], "crashy_function", "/Users/james/src/bugsnag/bugsnag-php/testing.php", 22);
        $this->assertFrameEquals($stacktrace[1], "parent_of_crashy_function", "/Users/james/src/bugsnag/bugsnag-php/testing.php", 13);
        $this->assertFrameEquals($stacktrace[2], "[main]", "/Users/james/src/bugsnag/bugsnag-php/testing.php", 25);
    }

    public function testExceptionHandlerStacktrace()
    {
        $fixture = $this->getFixture('backtraces/exception_handler.json');
        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], $fixture['file'], $fixture['line'])->toArray();

        $this->assertCount(3, $stacktrace);

        $this->assertFrameEquals($stacktrace[0], "crashy_function", "/Users/james/src/bugsnag/bugsnag-php/testing.php", 25);
        $this->assertFrameEquals($stacktrace[1], "parent_of_crashy_function", "/Users/james/src/bugsnag/bugsnag-php/testing.php", 13);
        $this->assertFrameEquals($stacktrace[2], "[main]", "/Users/james/src/bugsnag/bugsnag-php/testing.php", 28);
    }

    public function testAnonymousFunctionStackframes()
    {
        $fixture = $this->getFixture('backtraces/anonymous_frame.json');
        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], "somefile.php", 123)->toArray();

        $this->assertCount(5, $stacktrace);

        $this->assertFrameEquals($stacktrace[0], "__callStatic", "somefile.php", 123);
        $this->assertFrameEquals($stacktrace[1], "notifyError", "controllers/ExampleController.php", 12);
        $this->assertFrameEquals($stacktrace[2], "index", "controllers/ExampleController.php", 12);
        $this->assertFrameEquals($stacktrace[3], "call_user_func_array", "[internal]", 0);
        $this->assertFrameEquals($stacktrace[4], "[main]", "Routing/Controller.php", 194);
    }

    public function testPopFrameGetsFrame()
    {
        $stacktrace = Bugsnag_Stacktrace::fromFrame($this->config, "some_file.php", 123);

        $poppedFrame = $stacktrace->popFrame();
        $this->assertInternalType("array", $poppedFrame);
        $this->assertEquals("some_file.php", $poppedFrame['file']);
        $this->assertEquals(123, $poppedFrame['lineNumber']);
    }

    public function testPopFrameGetsLastFrame()
    {
        $stacktrace = Bugsnag_Stacktrace::fromFrame($this->config, "some_file.php", 123);
        $stacktrace->addFrame("some_other_file.php", 65, "myMethod");

        $poppedFrame = $stacktrace->popFrame();
        $this->assertInternalType("array", $poppedFrame);
        $this->assertEquals("some_other_file.php", $poppedFrame['file']);
        $this->assertEquals(65, $poppedFrame['lineNumber']);
    }

    public function testPopFrameReturnsNullWhenEmpty()
    {
        $stacktrace = Bugsnag_Stacktrace::fromFrame($this->config, "some_file.php", 123);

        $poppedFrame = $stacktrace->popFrame();
        $secondFrame = $stacktrace->popFrame();

        $this->assertNull($secondFrame);
    }
}
