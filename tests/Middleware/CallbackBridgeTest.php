<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Middleware\CallbackBridge;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class CallbackBridgeTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
    }

    public function testCallback()
    {
        $this->expectOutputString('1reached');

        $middleware = new CallbackBridge(function ($error) {
            echo $error instanceof Error;
        });

        $middleware(Error::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'reached';
        });
    }

    public function testSkips()
    {
        $this->expectOutputString('');

        $middleware = new CallbackBridge(function ($error) {
            return false;
        });

        $middleware(Error::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'reached';
        });
    }
}
