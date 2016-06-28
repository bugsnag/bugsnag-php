<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Diagnostics;
use Bugsnag\Error;
use Bugsnag\Middleware\AddGlobalMetaData;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class AddGlobalMetaDataTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Diagnostics */
    protected $diagnostics;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
        $this->diagnostics = new Diagnostics($this->config, new BasicResolver());
    }

    public function testCanAddMetaData()
    {
        $error = Error::fromPHPThrowable($this->config, $this->diagnostics, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddGlobalMetaData($this->config);

        $this->config->metaData = ['foo' => 'bar'];

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz', 'foo' => 'bar'], $error->metaData);
    }

    public function testCanDoNothing()
    {
        $error = Error::fromPHPThrowable($this->config, $this->diagnostics, new Exception())->setMetaData(['bar' => 'baz']);

        $middleware = new AddGlobalMetaData($this->config);

        $middleware($error, function () {
            //
        });

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }
}
