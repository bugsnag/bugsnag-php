<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Middleware\NotificationSkipper;
use Bugsnag\Report;
use Bugsnag\Tests\TestCase;
use Exception;

class NotificationSkipperTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
    }

    public function testDefaultReleaseStageShouldNotify()
    {
        $this->expectOutputString('NOTIFIED');

        $middleware = new NotificationSkipper($this->config);

        $middleware(Report::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testCustomReleaseStageShouldNotify()
    {
        $this->config->setReleaseStage('staging');

        $this->expectOutputString('NOTIFIED');

        $middleware = new NotificationSkipper($this->config);

        $middleware(Report::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testCustomNotifyReleaseStagesShouldNotify()
    {
        $this->config->setNotifyReleaseStages(['banana']);

        $this->expectOutputString('');

        $middleware = new NotificationSkipper($this->config);

        $middleware(Report::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testBothCustomShouldNotify()
    {
        $this->config->setReleaseStage('banana');
        $this->config->setNotifyReleaseStages(['banana']);

        $this->expectOutputString('NOTIFIED');

        $middleware = new NotificationSkipper($this->config);

        $middleware(Report::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }
}
