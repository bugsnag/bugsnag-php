<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\CustomUser;
use Bugsnag\Configuration;
use Bugsnag\Files\Filesystem;
use Bugsnag\Report;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class CustomUserTest extends TestCase
{
    protected $config;
    protected $filesystem;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
        $this->filesystem = new Filesystem();
    }

    public function testCanUser()
    {
        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setUser(['bar' => 'baz']);

        $callback = new CustomUser(function () {
            return ['foo' => 123];
        });

        $callback($report);

        $this->assertSame(['foo' => 123], $report->getUser());
    }

    public function testCanDoNothing()
    {
        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setUser(['bar' => 'baz']);

        $callback = new CustomUser(function () {
            // do nothing
        });

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getUser());
    }

    public function testCanBehaveUnderAnException()
    {
        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setUser(['bar' => 'baz']);

        $callback = new CustomUser(function () {
            throw new Exception();
        });

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getUser());
    }
}
