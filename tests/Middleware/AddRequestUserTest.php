<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Middleware\AddRequestUser;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class AddRequestUserTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Request\ResolverInterface */
    protected $resolver;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
        $this->resolver = new BasicResolver();
    }

    public function testCanAddUser()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $middleware = new AddRequestUser($this->resolver);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['id' => '123.45.67.8'], $error->user);
    }

    public function testCanAddForwardedUser()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '321.42.42.42';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $middleware = new AddRequestUser($this->resolver);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['id' => '321.42.42.42'], $error->user);
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $middleware = new AddRequestUser($this->resolver);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz'], $error->user);
    }

    public function testFallsBackToNull()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $middleware = new AddRequestUser($this->resolver);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz'], $error->user);
    }
}
