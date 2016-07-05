<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Callbacks\GlobalMetaData;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class GlobalMetaDataTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
    }

    public function testCanMetaData()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new GlobalMetaData($this->config);

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($error);

        $this->assertSame(['bar' => 'baz', 'foo' => 'bar'], $error->metaData);
    }

    public function testCanDoNothing()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new GlobalMetaData($this->config);

        $callback($error);

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }
}
