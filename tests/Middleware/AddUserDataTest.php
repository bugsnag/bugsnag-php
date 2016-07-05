<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Middleware\AddUserData;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class AddUserDataTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
    }

    public function testCanAddUser()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $middleware = new AddUserData(function () {
            return ['foo' => 123];
        });

        $middleware($error, function () {
            //
        });

        $this->assertSame(['foo' => 123], $error->user);
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $middleware = new AddUserData(function () {
            // do nothing
        });

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz'], $error->user);
    }
}
