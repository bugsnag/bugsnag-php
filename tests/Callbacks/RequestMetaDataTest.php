<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestMetaData;
use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class RequestMetaDataTest extends TestCase
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

    public function testCanMetaData()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/blah/blah.php?some=param';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Example Browser 1.2.3';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestMetaData($this->resolver);

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($error);

        $this->assertSame(['bar' => 'baz', 'request' => [
            'url' => 'http://example.com/blah/blah.php?some=param',
            'httpMethod' => 'GET',
            'params' => null,
            'clientIp' => '123.45.67.8',
            'userAgent' => 'Example Browser 1.2.3',
            'headers' => ['Host' => 'example.com', 'User-Agent' => 'Example Browser 1.2.3'],
        ]], $error->getMetaData());
    }

    public function testFallsBackToNull()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestMetaData($this->resolver);

        $callback($error);

        $this->assertSame(['bar' => 'baz'], $error->getMetaData());
    }
}
