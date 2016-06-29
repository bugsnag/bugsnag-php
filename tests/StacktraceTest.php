<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Stacktrace;

class StacktraceTest extends AbstractTestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('key');
    }

    protected function assertFrameEquals($frame, $method, $file, $line)
    {
        $this->assertSame($frame['method'], $method);
        $this->assertSame($frame['file'], $file);
        $this->assertSame($frame['lineNumber'], $line);
    }

    protected function assertCodeEquals($expected, $actual)
    {
        $this->assertSame(rtrim(substr($expected, 0, 200)), $actual);
    }

    public function testFromFrame()
    {
        $stacktrace = Stacktrace::fromFrame($this->config, 'some_file.php', 123)->toarray();

        $this->assertFrameEquals($stacktrace[0], '[unknown]', 'some_file.php', 123);

        $this->assertCount(1, $stacktrace);
    }

    public function testFrameInsideBugsnag()
    {
        $frame = $this->getJsonFixture('frames/non_bugsnag.json');
        $bugsnagFrame = $this->getJsonFixture('frames/bugsnag.json');

        $this->assertFalse(Stacktrace::frameInsideBugsnag($frame));
        $this->assertTrue(Stacktrace::frameInsideBugsnag($bugsnagFrame));
    }

    public function testTriggeredErrorStacktrace()
    {
        $fixture = $this->getJsonFixture('backtraces/trigger_error.json');
        $stacktrace = Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], $fixture['file'], $fixture['line'])->toArray();

        $this->assertFrameEquals($stacktrace[0], 'trigger_error', '[internal]', 0);
        $this->assertFrameEquals($stacktrace[1], 'crashy_function', '/Users/james/src/bugsnag/bugsnag-php/testing.php', 17);
        $this->assertFrameEquals($stacktrace[2], 'parent_of_crashy_function', '/Users/james/src/bugsnag/bugsnag-php/testing.php', 13);
        $this->assertFrameEquals($stacktrace[3], '[main]', '/Users/james/src/bugsnag/bugsnag-php/testing.php', 20);

        $this->assertCount(4, $stacktrace);
    }

    public function testErrorHandlerStacktrace()
    {
        $fixture = $this->getJsonFixture('backtraces/error_handler.json');
        $stacktrace = Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], $fixture['file'], $fixture['line'])->toArray();

        $this->assertFrameEquals($stacktrace[0], 'crashy_function', '/Users/james/src/bugsnag/bugsnag-php/testing.php', 22);
        $this->assertFrameEquals($stacktrace[1], 'parent_of_crashy_function', '/Users/james/src/bugsnag/bugsnag-php/testing.php', 13);
        $this->assertFrameEquals($stacktrace[2], '[main]', '/Users/james/src/bugsnag/bugsnag-php/testing.php', 25);

        $this->assertCount(3, $stacktrace);
    }

    public function testExceptionHandlerStacktrace()
    {
        $fixture = $this->getJsonFixture('backtraces/exception_handler.json');
        $stacktrace = Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], $fixture['file'], $fixture['line'])->toArray();

        $this->assertFrameEquals($stacktrace[0], 'crashy_function', '/Users/james/src/bugsnag/bugsnag-php/testing.php', 25);
        $this->assertFrameEquals($stacktrace[1], 'parent_of_crashy_function', '/Users/james/src/bugsnag/bugsnag-php/testing.php', 13);
        $this->assertFrameEquals($stacktrace[2], '[main]', '/Users/james/src/bugsnag/bugsnag-php/testing.php', 28);

        $this->assertCount(3, $stacktrace);
    }

    public function testAnonymousFunctionStackframes()
    {
        $fixture = $this->getJsonFixture('backtraces/anonymous_frame.json');
        $stacktrace = Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], 'somefile.php', 123)->toArray();

        $this->assertFrameEquals($stacktrace[0], 'Illuminate\\Support\\Facades\\Facade::__callStatic', 'somefile.php', 123);
        $this->assertFrameEquals($stacktrace[1], 'Bugsnag\\BugsnagLaravel\\BugsnagFacade::notifyError', 'controllers/ExampleController.php', 12);
        $this->assertFrameEquals($stacktrace[2], 'ExampleController::index', 'controllers/ExampleController.php', 12);
        $this->assertFrameEquals($stacktrace[3], 'call_user_func_array', '[internal]', 0);
        $this->assertFrameEquals($stacktrace[4], '[main]', 'Routing/Controller.php', 194);

        $this->assertCount(5, $stacktrace);
    }

    public function testXdebugErrorStackframes()
    {
        $fixture = $this->getJsonFixture('backtraces/xdebug_error.json');
        $stacktrace = Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], $fixture['file'], $fixture['line'])->toArray();

        $this->assertFrameEquals($stacktrace[0], null, 'somefile.php', 123);
        $this->assertFrameEquals($stacktrace[1], 'Illuminate\\View\\Engines\\PhpEngine::evaluatePath', '/View/Engines/PhpEngine.php', 39);
        $this->assertFrameEquals($stacktrace[2], 'Illuminate\\View\\Engines\\CompilerEngine::get', 'View/Engines/CompilerEngine.php', 57);
        $this->assertFrameEquals($stacktrace[3], 'Illuminate\\View\\View::getContents', 'View/View.php', 136);
        $this->assertFrameEquals($stacktrace[4], 'Illuminate\\View\\View::renderContents', 'View/View.php', 104);
        $this->assertFrameEquals($stacktrace[5], 'Illuminate\\View\\View::render', 'View/View.php', 78);
        $this->assertFrameEquals($stacktrace[6], '[main]', 'storage/views/f2df2d6b49591efeb36fc46e6dc25e0e', 5);

        $this->assertCount(7, $stacktrace);
    }

    public function testEvaledStackframes()
    {
        $evalFrame = $this->getJsonFixture('frames/eval.json');
        $stacktrace = Stacktrace::fromFrame($this->config, $evalFrame['file'], $evalFrame['line'])->toArray();
        $topFrame = $stacktrace[0];

        $this->assertSame($topFrame['file'], 'path/some/file.php');
        $this->assertSame($topFrame['lineNumber'], 123);

        $evalFrame = $this->getJsonFixture('frames/runtime_created.json');
        $stacktrace = Stacktrace::fromFrame($this->config, $evalFrame['file'], $evalFrame['line'])->toArray();
        $topFrame = $stacktrace[0];

        $this->assertSame($topFrame['file'], 'path/some/file.php');
        $this->assertSame($topFrame['lineNumber'], 123);
    }

    public function testStrippingPaths()
    {
        $fixture = $this->getJsonFixture('backtraces/exception_handler.json');
        $this->config->setStripPath('/Users/james/src/bugsnag/bugsnag-php/');
        $stacktrace = Stacktrace::fromBacktrace($this->config, $fixture['backtrace'], $fixture['file'], $fixture['line'])->toArray();

        $this->assertFrameEquals($stacktrace[0], 'crashy_function', 'testing.php', 25);
        $this->assertFrameEquals($stacktrace[1], 'parent_of_crashy_function', 'testing.php', 13);
        $this->assertFrameEquals($stacktrace[2], '[main]', 'testing.php', 28);

        $this->assertCount(3, $stacktrace);
    }

    public function testCode()
    {
        $fileContents = explode("\n", $this->getFixture('code/File.php'));
        $stacktrace = Stacktrace::fromFrame($this->config, $this->getFixturePath('code/File.php'), 12)->toArray();
        $this->assertCount(1, $stacktrace);

        $topFrame = $stacktrace[0];
        $this->assertCount(7, $topFrame['code']);

        for ($i = 9; $i <= 15; $i++) {
            $this->assertCodeEquals($fileContents[$i - 1], $topFrame['code'][$i]);
        }
    }

    public function testCodeShortFile()
    {
        $fileContents = explode("\n", $this->getFixture('code/ShortFile.php'));
        $stacktrace = Stacktrace::fromFrame($this->config, $this->getFixturePath('code/ShortFile.php'), 1)->toArray();
        $this->assertCount(1, $stacktrace);

        $topFrame = $stacktrace[0];
        $this->assertCount(3, $topFrame['code']);

        for ($i = 1; $i <= 2; $i++) {
            $this->assertCodeEquals($fileContents[$i - 1], $topFrame['code'][$i]);
        }
    }

    public function testCodeEndOfFile()
    {
        $fileContents = explode("\n", $this->getFixture('code/File.php'));
        $stacktrace = Stacktrace::fromFrame($this->config, $this->getFixturePath('code/File.php'), 20)->toArray();
        $this->assertCount(1, $stacktrace);

        $topFrame = $stacktrace[0];
        $this->assertCount(7, $topFrame['code']);

        for ($i = 16; $i <= 20; $i++) {
            $this->assertCodeEquals($fileContents[$i - 1], $topFrame['code'][$i]);
        }
    }

    public function testCodeStartOfFile()
    {
        $fileContents = explode("\n", $this->getFixture('code/File.php'));
        $stacktrace = Stacktrace::fromFrame($this->config, $this->getFixturePath('code/File.php'), 1)->toArray();
        $this->assertCount(1, $stacktrace);

        $topFrame = $stacktrace[0];
        $this->assertCount(7, $topFrame['code']);

        for ($i = 1; $i <= 7; $i++) {
            $this->assertCodeEquals($fileContents[$i - 1], $topFrame['code'][$i]);
        }
    }

    public function testCodeDisabled()
    {
        $config = new Configuration('key');
        $config->sendCode = false;

        $stacktrace = Stacktrace::fromFrame($config, $this->getFixturePath('code/File.php'), 1)->toArray();
        $this->assertCount(1, $stacktrace);

        $topFrame = $stacktrace[0];
        $this->assertArrayNotHasKey('code', $topFrame);
    }
}
