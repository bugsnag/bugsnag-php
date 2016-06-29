<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Middleware\AddGlobalMetaData;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class AddGlobalMetaDataTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
    }

    public function testCanAddMetaData()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddGlobalMetaData($this->config);

        $this->config->setMetaData(['foo' => 'bar']);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz', 'foo' => 'bar'], $error->metaData);
    }

    public function testCanDoNothing()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddGlobalMetaData($this->config);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }
}
