<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Diagnostics;
use Bugsnag\Error;
use Bugsnag\Middleware\AddRequestMetaData;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class AddRequestMetaDataTest extends TestCase
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
        $_SERVER['REQUEST_URI'] = '/blah/blah.php?some=param';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Example Browser 1.2.3';

        $error = Error::fromPHPThrowable($this->config, $this->diagnostics, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddRequestMetaData($this->resolver);

        $this->config->metaData = ['foo' => 'bar'];

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz', 'request' => [
            'url' => 'http://example.com/blah/blah.php?some=param',
            'httpMethod' => 'GET',
            'params' => null,
            'clientIp' => '123.45.67.8',
            'userAgent' => 'Example Browser 1.2.3',
            'headers' => ['Host' => 'example.com', 'User-Agent' => 'Example Browser 1.2.3']
        ]], $error->metaData);
    }

    public function testCanDoNothing()
    {
        $error = Error::fromPHPThrowable($this->config, $this->diagnostics, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddRequestMetaData($this->resolver);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }
}
