<?php

namespace Bugsnag\Tests;

use Bugsnag\Request;
use PHPUnit_Framework_TestCase as TestCase;

class RequestTest extends TestCase
{
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
    }

    public function testIsRequest()
    {
        $this->assertTrue(Request::isRequest());
    }

    public function testGetContext()
    {
        $this->assertSame(Request::getContext(), 'GET /blah/blah.php');
    }

    public function testGetCurrentUrl()
    {
        $this->assertSame(Request::getCurrentUrl(), 'http://example.com/blah/blah.php?some=param');
    }

    public function testRequestIp()
    {
        $this->assertSame(Request::getRequestIp(), '123.45.67.8');
    }
}
