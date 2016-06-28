<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Diagnostics;
use Bugsnag\Error;
use Bugsnag\Middleware\AddRequestCookieData;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class AddRequestCookieDataTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Diagnostics */
    protected $diagnostics;
    /** @var \Bugsnag\Request\ResolverInterface */
    protected $resolver;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
        $this->diagnostics = new Diagnostics($this->config, $this->resolver = new BasicResolver());
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanAddMetaData()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_COOKIE = ['cookie' => 'cookieval'];

        $error = Error::fromPHPThrowable($this->config, $this->diagnostics, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddRequestCookieData($this->resolver);

        $this->config->metaData = ['foo' => 'bar'];

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz', 'cookies' => ['cookie' => 'cookieval']], $error->metaData);
    }

    public function testCanDoNothing()
    {
        $error = Error::fromPHPThrowable($this->config, $this->diagnostics, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddRequestCookieData($this->resolver);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }
}
