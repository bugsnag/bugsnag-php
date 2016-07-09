<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\GlobalMetaData;
use Bugsnag\Configuration;
use Bugsnag\Files\Filesystem;
use Bugsnag\Report;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class GlobalMetaDataTest extends TestCase
{
    protected $config;
    protected $filesystem;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
        $this->filesystem = new Filesystem();
    }

    public function testCanMetaData()
    {
        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new GlobalMetaData($this->config);

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($report);

        $this->assertSame(['bar' => 'baz', 'foo' => 'bar'], $report->getMetaData());
    }

    public function testCanDoNothing()
    {
        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new GlobalMetaData($this->config);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }
}
