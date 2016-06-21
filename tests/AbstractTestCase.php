<?php

namespace Bugsnag\Tests;

use Bugsnag\Error;
use PHPUnit_Framework_TestCase as TestCase;

abstract class AbstractTestCase extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Diagnostics */
    protected $diagnostics;

    protected function getError($name = 'Name', $message = 'Message')
    {
        return Error::fromNamedError($this->config, $this->diagnostics, $name, $message);
    }

    protected function getFixturePath($file)
    {
        return realpath(dirname(__FILE__).'/../fixtures/'.$file);
    }

    protected function getFixture($file)
    {
        return file_get_contents($this->getFixturePath($file));
    }

    protected function getJsonFixture($file)
    {
        return json_decode($this->getFixture($file), true);
    }
}
