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

    public function testBugsnagFramesDiscarded()
    {
        $backtrace = array(
            array(
                'file' => 'Bugsnag/Client.php',
                'line' => '123',
                'class' => 'Bugsnag_Client',
                'function' => 'example_function'
            ),
            array(
                'file' => 'Bugsnag/Configuration.php',
                'line' => '20',
                'class' => 'Bugsnag_Configuration',
                'function' => 'example_function'
            ),
            array(
                'file' => 'anotherfile.php',
                'line' => '456',
                'class' => 'MyAppClass',
                'function' => 'another_function'
            )
        );

        $stacktrace = Bugsnag_Stacktrace::fromBacktrace($this->config, $backtrace, "somefile.php", 123);
        $this->assertEquals(count($stacktrace->toArray()), 2);
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
