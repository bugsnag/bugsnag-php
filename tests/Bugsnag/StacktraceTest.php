<?php

class StacktraceTest extends PHPUnit_Framework_TestCase
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
        $frame = array(
            'file' => 'controllers/ExampleController.php',
            'line' => '12',
            'class' => 'Illuminate\Support\Facades\Facade',
            'function' => '__callStatic'
        );

        $bugsnagFrame = array(
            'file' => 'Bugsnag/Client.php',
            'line' => '123',
            'class' => 'Bugsnag_Client',
            'function' => 'example_function'
        );

        $this->assertEquals(Bugsnag_Stacktrace::frameInsideBugsnag($frame), false);
        $this->assertEquals(Bugsnag_Stacktrace::frameInsideBugsnag($bugsnagFrame), true);
    }

    public function testTriggeredErrorStacktrace()
    {
        $topFile = "/Users/james/src/bugsnag/bugsnag-php/testing.php";
        $topLine = 17;
        $backtrace = array(
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/src/Bugsnag/Error.php",
                "line" => 116,
                "function" => "generate",
                "class" => "Bugsnag_Stacktrace"
            ),
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/src/Bugsnag/Error.php",
                "line" => 25,
                "function" => "setPHPError",
                "class" => "Bugsnag_Error"
            ),
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/src/Bugsnag/Client.php",
                "line" => 346,
                "function" => "fromPHPError",
                "class" => "Bugsnag_Error"
            ),
            array(
                "function" => "errorHandler",
                "class" => "Bugsnag_Client"
            ),
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/testing.php",
                "line" => 17,
                "function" => "trigger_error"
            ),
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/testing.php",
                "line" => 13,
                "function" => "crashy_function"
            ),
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/testing.php",
                "line" => 20,
                "function" => "parent_of_crashy_function"
            )
        );

        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $backtrace, $topFile, $topLine)->toArray();

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
        $topFile = "/Users/james/src/bugsnag/bugsnag-php/testing.php";
        $topLine = 22;
        $backtrace = array(
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/src/Bugsnag/Error.php",
                "line" => 116,
                "function" => "generate",
                "class" => "Bugsnag_Stacktrace"
            ),
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/src/Bugsnag/Error.php",
                "line" => 25,
                "function" => "setPHPError",
                "class" => "Bugsnag_Error"
            ),
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/src/Bugsnag/Client.php",
                "line" => 346,
                "function" => "fromPHPError",
                "class" => "Bugsnag_Error"
            ),
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/testing.php",
                "line" => 22,
                "function" => "errorHandler",
                "class" => "Bugsnag_Client"
            ),
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/testing.php",
                "line" => 13,
                "function" => "crashy_function"
            ),
            array(
                "file" => "/Users/james/src/bugsnag/bugsnag-php/testing.php",
                "line" => 25,
                "function" => "parent_of_crashy_function"
            )
        );

        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $backtrace, $topFile, $topLine)->toArray();

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

    public function testIncompleteStackframes()
    {
        $backtrace = array(
            array(
                'file' => 'controllers/ExampleController.php',
                'line' => '12',
                'class' => 'Illuminate\Support\Facades\Facade',
                'function' => '__callStatic'
            ),
            array(
                'file' => 'controllers/ExampleController.php',
                'line' => '12',
                'class' => 'Bugsnag\BugsnagLaravel\BugsnagFacade',
                'function' => 'notifyError'
            ),
            array(
                'class' => 'ExampleController',
                'function' => 'index'
            ),
            array(
                'file' => 'Routing/Controller.php',
                'line' => '194',
                'function' => 'call_user_func_array'
            )
        );

        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $backtrace, "somefile.php", 123);
        $this->assertEquals(count($stacktrace->toArray()), 5);
    }
}
