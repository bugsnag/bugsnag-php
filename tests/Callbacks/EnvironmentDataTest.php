<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Callbacks\EnvironmentData;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class EnvironmentDataTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
    }

    public function testCanEnvData()
    {
        foreach (array_keys($_ENV) as $env) {
            unset($_ENV[$env]);
        }

        $_ENV['SOMETHING'] = 'blah';

        $error = Error::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new EnvironmentData();

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($error, function () {
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

        $callback = new EnvironmentData();

        $callback($error);

        $this->assertSame(['bar' => 'baz'], $error->metaData);
    }
}
