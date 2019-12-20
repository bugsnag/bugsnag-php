<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\GlobalMetaData;
use Bugsnag\Configuration;
use Bugsnag\Report;
use Bugsnag\Tests\TestCase;
use Exception;

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
        $report = Report::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new GlobalMetaData($this->config);

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($report);

        $this->assertSame(['bar' => 'baz', 'foo' => 'bar'], $report->getMetaData());
    }

    public function testCanDoNothing()
    {
        $report = Report::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new GlobalMetaData($this->config);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }
}
