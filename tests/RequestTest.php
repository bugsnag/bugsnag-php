<?php

namespace Bugsnag\Tests;

use Bugsnag\Request\BasicResolver;
use Bugsnag\Request\ConsoleRequest;
use Bugsnag\Request\NullRequest;
use Bugsnag\Request\PhpRequest;
use Bugsnag\Request\RequestInterface;
use Bugsnag\Request\ResolverInterface;
use ReflectionClass;

class RequestTest extends TestCase
{
    protected $resolver;

    protected function setUp()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/blah/blah.php?some=param';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Example Browser 1.2.3';

        $this->resolver = new StubbedResolver();
    }

    public function testResolverInterface()
    {
        $this->assertTrue($this->resolver instanceof ResolverInterface);
    }

    public function testIsRequest()
    {
        $this->assertTrue($this->resolver->resolve()->isRequest());
    }

    public function testIsNotRequestWithConsole()
    {
        $_SERVER['argv'] = ['test', 'command', 'string'];
        unset($_SERVER['REQUEST_METHOD']);
        $this->assertFalse($this->resolver->resolve()->isRequest());
    }

    public function testNotRequestOrConsole()
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['argv']);
        $this->assertFalse($this->resolver->resolve()->isRequest());
    }

    public function testIsPhpRequest()
    {
        $this->assertTrue($this->resolver->resolve() instanceof PhpRequest);
        $this->assertTrue($this->resolver->resolve() instanceof RequestInterface);
    }

    public function testIsConsoleRequest()
    {
        $_SERVER['argv'] = ['test', 'command', 'string'];
        unset($_SERVER['REQUEST_METHOD']);
        $this->assertTrue($this->resolver->resolve() instanceof ConsoleRequest);
        $this->assertTrue($this->resolver->resolve() instanceof RequestInterface);
    }

    public function testIsNullRequest()
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['argv']);
        $this->assertTrue($this->resolver->resolve() instanceof NullRequest);
        $this->assertTrue($this->resolver->resolve() instanceof RequestInterface);
    }

    public function testCookie()
    {
        $_COOKIE = ['cookie' => 'cookieval'];

        $this->assertSame(['cookie' => 'cookieval'], $this->resolver->resolve()->getCookies());
    }

    public function testSession()
    {
        $_SESSION = ['session' => 'sessionval'];

        $this->assertSame(['session' => 'sessionval'], $this->resolver->resolve()->getSession());
    }

    public function testGetMetaDataWithPost()
    {
        $_POST['foo'] = 'bar';

        $data = [
            'url' => 'http://example.com/blah/blah.php?some=param',
            'httpMethod' => 'PUT',
            'params' => ['foo' => 'bar'],
            'clientIp' => '123.45.67.8',
            'userAgent' => 'Example Browser 1.2.3',
            'headers' => ['Host' => 'example.com', 'User-Agent' => 'Example Browser 1.2.3'],
        ];

        $this->assertSame(['request' => $data], $this->resolver->resolve()->getMetaData());
    }

    public function testGetMetaDataWithGet()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['foo'] = 'foo';
        $_GET['bar'] = 'bar';
        $data = [
            'url' => 'http://example.com/blah/blah.php?some=param',
            'httpMethod' => 'GET',
            'params' => ['foo' => 'foo', 'bar' => 'bar'],
            'clientIp' => '123.45.67.8',
            'userAgent' => 'Example Browser 1.2.3',
            'headers' => ['Host' => 'example.com', 'User-Agent' => 'Example Browser 1.2.3'],
        ];

        $this->assertSame(['request' => $data], $this->resolver->resolve()->getMetaData());
    }

    public function testGetMetaDataWithJsonInput()
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        $data = [
            'url' => 'http://example.com/blah/blah.php?some=param',
            'httpMethod' => 'PUT',
            'params' => ['foo' => 'baz'],
            'clientIp' => '123.45.67.8',
            'userAgent' => 'Example Browser 1.2.3',
            'headers' => ['Host' => 'example.com', 'User-Agent' => 'Example Browser 1.2.3'],
        ];

        $this->assertSame(['request' => $data], $this->resolver->resolve()->getMetaData());
    }

    public function testGetMetaDataWithPutInput()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $data = [
            'url' => 'http://example.com/blah/blah.php?some=param',
            'httpMethod' => 'PUT',
            'params' => ['test' => 'foo'],
            'clientIp' => '123.45.67.8',
            'userAgent' => 'Example Browser 1.2.3',
            'headers' => ['Host' => 'example.com', 'User-Agent' => 'Example Browser 1.2.3'],
        ];

        $this->assertSame(['request' => $data], $this->resolver->resolve()->getMetaData());
    }

    public function testGetMetaDataWithPostFormInput()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $data = [
            'url' => 'http://example.com/blah/blah.php?some=param',
            'httpMethod' => 'POST',
            'params' => null,
            'clientIp' => '123.45.67.8',
            'userAgent' => 'Example Browser 1.2.3',
            'headers' => ['Host' => 'example.com', 'User-Agent' => 'Example Browser 1.2.3'],
        ];

        $this->assertSame(['request' => $data], $this->resolver->resolve()->getMetaData());
    }

    public function testGetMetaDataWithGetInput()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

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
        $this->assertSame('PUT /blah/blah.php', $this->resolver->resolve()->getContext());
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

class StubbedResolver extends BasicResolver
{
    protected static function readInput()
    {
        return isset($_SERVER['CONTENT_TYPE']) ? json_encode(['foo' => 'baz']) : 'test=foo';
    }
}
