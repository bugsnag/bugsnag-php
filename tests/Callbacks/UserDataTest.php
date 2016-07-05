<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Callbacks\CustomUser;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class CustomUserTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
    }

    public function testCanUser()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $callback = new CustomUser(function () {
            return ['foo' => 123];
        });

        $callback($error);

        $this->assertSame(['foo' => 123], $error->user);
    }

    public function testCanDoNothing()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $callback = new CustomUser(function () {
            // do nothing
        });

        $callback($error);

        $this->assertSame(['bar' => 'baz'], $error->user);
    }

    public function testCanBehaveUnderAnException()
    {

        $error = Error::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $callback = new CustomUser(function () {
            throw new Exception();
        });

        $callback($error);

        $this->assertSame(['bar' => 'baz'], $error->user);
    }
}
