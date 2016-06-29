<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Middleware\AddRequestContext;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class AddRequestContextTest extends TestCase
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

    public function testCanAddContext()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/blah/blah.php?some=param';

        $error = Error::fromPHPThrowable($this->config, new Exception());

        $middleware = new AddRequestContext($this->resolver);

        $middleware($error, function () {
            //
        });

        $this->assertSame('PUT /blah/blah.php', $error->context);
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $error = Error::fromPHPThrowable($this->config, new Exception());

        $middleware = new AddRequestContext($this->resolver);

        $middleware($error, function () {
            //
        });

        $this->assertSame(null, $error->context);
    }

    public function testFallsBackToNull()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception());

        $middleware = new AddRequestContext($this->resolver);

        $middleware($error, function () {
            //
        });

        $this->assertSame(null, $error->context);
    }
}
