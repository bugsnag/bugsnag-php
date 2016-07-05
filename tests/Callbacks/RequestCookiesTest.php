<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestCookies;
use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class RequestCookiesTest extends TestCase
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

    public function testCanCookie()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_COOKIE = ['cookie' => 'cookieval'];

        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestCookies($this->resolver);

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($error);

        $this->assertSame(['bar' => 'baz', 'cookies' => ['cookie' => 'cookieval']], $error->metaData);
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestCookies($this->resolver);

        $callback($error);

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }

    public function testFallsBackToNull()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestCookies($this->resolver);

        $callback($error);

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }
}
