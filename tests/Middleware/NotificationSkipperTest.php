<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Files\Filesystem;
use Bugsnag\Middleware\NotificationSkipper;
use Bugsnag\Report;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class NotificationSkipperTest extends TestCase
{
    protected $config;
    protected $filesystem;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
        $this->filesystem = new Filesystem();
    }

    public function testDefaultReleaseStageShouldNotify()
    {
        $this->expectOutputString('NOTIFIED');

        $middleware = new NotificationSkipper($this->config);

        $middleware(Report::fromPHPThrowable($this->config, $this->filesystem, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testCustomReleaseStageShouldNotify()
    {
        $this->config->setReleaseStage('staging');

        $this->expectOutputString('NOTIFIED');

        $middleware = new NotificationSkipper($this->config);

        $middleware(Report::fromPHPThrowable($this->config, $this->filesystem, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testCustomNotifyReleaseStagesShouldNotify()
    {
        $this->config->setNotifyReleaseStages(['banana']);

        $this->expectOutputString('');

        $middleware = new NotificationSkipper($this->config);

        $middleware(Report::fromPHPThrowable($this->config, $this->filesystem, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testBothCustomShouldNotify()
    {
        $this->config->setReleaseStage('banana');
        $this->config->setNotifyReleaseStages(['banana']);

        $this->expectOutputString('NOTIFIED');

        $middleware = new NotificationSkipper($this->config);

        $middleware(Report::fromPHPThrowable($this->config, $this->filesystem, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }
}
