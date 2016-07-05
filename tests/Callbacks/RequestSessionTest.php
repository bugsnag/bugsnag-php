<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestSession;
use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class RequestSessionTest extends TestCase
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

    public function testCanSession()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION = ['session' => 'sessionval'];

        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestSession($this->resolver);

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($error);

        $this->assertSame(['bar' => 'baz', 'session' => ['session' => 'sessionval']], $error->metaData);
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestSession($this->resolver);

        $callback($error);

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }

    public function testFallsBackToNull()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestSession($this->resolver);

        $callback($error);

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }
}
