<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Middleware\AddRequestSessionData;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class AddRequestSessionDataTest extends TestCase
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

    public function testCanAddSessionData()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION = ['session' => 'sessionval'];

        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddRequestSessionData($this->resolver);

        $this->config->setMetaData(['foo' => 'bar']);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz', 'session' => ['session' => 'sessionval']], $error->metaData);
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddRequestSessionData($this->resolver);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }

    public function testFallsBackToNull()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddRequestSessionData($this->resolver);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }
}
