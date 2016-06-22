<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use PHPUnit_Framework_TestCase as TestCase;

class ConfigurationTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDoesNotAcceptBadApiKey()
    {
        new Configuration([]);
    }

    public function testDefaultEndpoint()
    {
        $this->assertSame($this->config->getNotifyEndpoint(), 'https://notify.bugsnag.com');
    }

    public function testCustomEndpoint()
    {
        $this->config->endpoint = 'http://localhost';
        $this->assertSame($this->config->getNotifyEndpoint(), 'http://localhost');
    }

    public function testDefaultReleaseStageShouldNotify()
    {
        $this->assertTrue($this->config->shouldNotify());
    }

    public function testCustomReleaseStageShouldNotify()
    {
        $this->config->releaseStage = 'staging';
        $this->assertTrue($this->config->shouldNotify());
    }

    public function testCustomNotifyReleaseStagesShouldNotify()
    {
        $this->config->notifyReleaseStages = ['banana'];
        $this->assertFalse($this->config->shouldNotify());
    }

    public function testBothCustomShouldNotify()
    {
        $this->config->releaseStage = 'banana';
        $this->config->notifyReleaseStages = ['banana'];
        $this->assertTrue($this->config->shouldNotify());
    }

    public function testNotifier()
    {
        $this->assertSame($this->config->notifier['name'], 'Bugsnag PHP (Official)');
        $this->assertSame($this->config->notifier['url'], 'https://bugsnag.com');
    }

    public function testShouldIgnore()
    {
        $this->config->errorReportingLevel = E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED;

        $this->assertTrue($this->config->shouldIgnoreErrorCode(E_NOTICE));
    }

    public function testShouldNotIgnore()
    {
        $this->config->errorReportingLevel = E_ALL;

        $this->assertfalse($this->config->shouldIgnoreErrorCode(E_NOTICE));
    }
}
