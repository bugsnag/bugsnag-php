<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestUser;
use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class RequestUserTest extends TestCase
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

    public function testCanUser()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $callback = new RequestUser($this->resolver);

        $callback($error);

        $this->assertSame(['id' => '123.45.67.8'], $error->getUser());
    }

    public function testCanForwardedUser()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '321.42.42.42';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $callback = new RequestUser($this->resolver);

        $callback($error);

        $this->assertSame(['id' => '321.42.42.42'], $error->getUser());
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $callback = new RequestUser($this->resolver);

        $callback($error);

        $this->assertSame(['bar' => 'baz'], $error->getUser());
    }

    public function testFallsBackToNull()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $callback = new RequestUser($this->resolver);

        $callback($error);

        $this->assertSame(['bar' => 'baz'], $error->getUser());
    }
}
