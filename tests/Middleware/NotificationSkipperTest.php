<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Middleware\NotificationSkipper;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

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

        $skipper = new NotificationSkipper($this->config);

        $skipper(Error::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testCustomReleaseStageShouldNotify()
    {
        $this->config->appData['releaseStage'] = 'staging';

        $this->expectOutputString('NOTIFIED');

        $skipper = new NotificationSkipper($this->config);

        $skipper(Error::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testCustomNotifyReleaseStagesShouldNotify()
    {
        $this->config->notifyReleaseStages = ['banana'];

        $this->expectOutputString('');

        $skipper = new NotificationSkipper($this->config);

        $skipper(Error::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }

    public function testBothCustomShouldNotify()
    {
        $this->config->appData['releaseStage'] = 'banana';
        $this->config->notifyReleaseStages = ['banana'];

        $this->expectOutputString('NOTIFIED');

        $skipper = new NotificationSkipper($this->config);

        $skipper(Error::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'NOTIFIED';
        });
    }
}
