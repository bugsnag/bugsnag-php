<?php

namespace Bugsnag\Tests;

use Bugsnag\Request\BasicResolver;
use Bugsnag\Request\NullRequest;
use Bugsnag\Request\PhpRequest;
use Bugsnag\Request\RequestInterface;
use Bugsnag\Request\ResolverInterface;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;

class RequestTest extends TestCase
{
    /** @var \Bugsnag\Request\ResolverInterface */
    protected $resolver;

    protected function setUp()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/blah/blah.php?some=param';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Example Browser 1.2.3';

        $_COOKIE = ['cookie' => 'cookieval'];
        $_SESSION = ['session' => 'sessionval'];

        $this->resolver = $resolver = new BasicResolver();
    }

    public function testResolverInterface()
    {
        $this->assertTrue($this->resolver instanceof ResolverInterface);
    }

    public function testIsRequest()
    {
        $this->assertTrue($this->resolver->resolve()->isRequest());
    }

    public function testNotRequest()
    {
        unset($_SERVER['REQUEST_METHOD']);

        $this->assertFalse($this->resolver->resolve()->isRequest());
    }

    public function testIsPhpRequest()
    {
        $this->assertTrue($this->resolver->resolve() instanceof PhpRequest);
        $this->assertTrue($this->resolver->resolve() instanceof RequestInterface);
    }

    public function testIsNullRequest()
    {
        unset($_SERVER['REQUEST_METHOD']);

        $this->assertTrue($this->resolver->resolve() instanceof NullRequest);
        $this->assertTrue($this->resolver->resolve() instanceof RequestInterface);
    }

    public function testCookie()
    {
        $this->assertSame(['cookie' => 'cookieval'], $this->resolver->resolve()->getCookieData());
    }

    public function testSession()
    {
        $this->assertSame(['session' => 'sessionval'], $this->resolver->resolve()->getSessionData());
    }

    public function testGetMetaData()
    {
        $data = [
            'url' => 'http://example.com/blah/blah.php?some=param',
            'httpMethod' => 'GET',
            'params' => null,
            'clientIp' => '123.45.67.8',
            'userAgent' => 'Example Browser 1.2.3',
            'headers' => ['Host' => 'example.com', 'User-Agent' => 'Example Browser 1.2.3'],
        ];

        $this->assertSame(['request' => $data], $this->resolver->resolve()->getMetaData());
    }

    public function testGetContext()
    {
        $this->assertSame('GET /blah/blah.php', $this->resolver->resolve()->getContext());
    }

    public function testGetCurrentUrl()
    {
        $request = $this->resolver->resolve();

        $method = (new ReflectionClass($request))->getMethod('getCurrentUrl');

        $method->setAccessible(true);

        $this->assertSame('http://example.com/blah/blah.php?some=param', $method->invoke($request));
    }

    public function testRequestIp()
    {
        $request = $this->resolver->resolve();

        $method = (new ReflectionClass($request))->getMethod('getRequestIp');

        $method->setAccessible(true);

        $this->assertSame('123.45.67.8', $method->invoke($request));
    }
}
