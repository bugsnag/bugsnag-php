<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\EnvironmentData;
use Bugsnag\Configuration;
use Bugsnag\Report;
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

        $report = Report::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new EnvironmentData();

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($report, function () {
            //
        });

        $this->assertSame(['bar' => 'baz', 'Environment' => ['SOMETHING' => 'blah']], $report->getMetaData());
    }

    public function testCanDoNothing()
    {
        foreach (array_keys($_ENV) as $env) {
            unset($_ENV[$env]);
        }

        $report = Report::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new EnvironmentData();

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }
}
