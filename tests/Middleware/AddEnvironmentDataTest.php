<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Middleware\AddEnvironmentData;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class AddEnvironmentDataTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
    }

    public function testCanAddEnvData()
    {
        foreach (array_keys($_ENV) as $env) {
            unset($_ENV[$env]);
        }

        $_ENV['SOMETHING'] = 'blah';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddEnvironmentData();

        $this->config->metaData = ['foo' => 'bar'];

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz', 'Environment' => ['SOMETHING' => 'blah']], $error->metaData);
    }

    public function testCanDoNothing()
    {
        foreach (array_keys($_ENV) as $env) {
            unset($_ENV[$env]);
        }

        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddEnvironmentData();

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }
}
