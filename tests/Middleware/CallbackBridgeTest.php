<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Files\Filesystem;
use Bugsnag\Middleware\CallbackBridge;
use Bugsnag\Report;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class CallbackBridgeTest extends TestCase
{
    protected $config;
    protected $filesystem;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
        $this->filesystem = new Filesystem();
    }

    public function testCallback()
    {
        $this->expectOutputString('1reached');

        $middleware = new CallbackBridge(function ($report) {
            echo $report instanceof Report;
        });

        $middleware(Report::fromPHPThrowable($this->config, $this->filesystem, new Exception()), function () {
            echo 'reached';
        });
    }

    public function testSkips()
    {
        $this->expectOutputString('');

        $middleware = new CallbackBridge(function ($report) {
            return false;
        });

        $middleware(Report::fromPHPThrowable($this->config, $this->filesystem, new Exception()), function () {
            echo 'reached';
        });
    }
}
