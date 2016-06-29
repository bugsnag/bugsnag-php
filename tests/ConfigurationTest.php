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

    public function testNotifier()
    {
        $this->assertSame($this->config->getNotifier()['name'], 'Bugsnag PHP (Official)');
        $this->assertSame($this->config->getNotifier()['url'], 'https://bugsnag.com');
    }

    public function testShouldIgnore()
    {
        $this->config->setErrorReportingLevel(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

        $this->assertTrue($this->config->shouldIgnoreErrorCode(E_NOTICE));
    }

    public function testShouldNotIgnore()
    {
        $this->config->setErrorReportingLevel(E_ALL);

        $this->assertfalse($this->config->shouldIgnoreErrorCode(E_NOTICE));
    }

    public function testAppData()
    {
        $this->assertSame(['releaseStage' => 'production'], $this->config->getAppData());

        $this->config->setReleaseStage('qa1');
        $this->config->setAppVersion('1.2.3');
        $this->config->setType('laravel');

        $this->assertSame(['releaseStage' => 'qa1', 'version' => '1.2.3', 'type' => 'laravel'], $this->config->getAppData());

        $this->config->setType(null);

        $this->assertSame(['releaseStage' => 'qa1', 'version' => '1.2.3'], $this->config->getAppData());

        $this->config->setReleaseStage(null);

        $this->assertSame(['releaseStage' => 'production', 'version' => '1.2.3'], $this->config->getAppData());
    }

    public function testDeviceData()
    {
        $this->assertSame(['hostname' => php_uname('n')], $this->config->getDeviceData());

        $this->config->setHostname('web1.example.com');

        $this->assertSame(['hostname' => 'web1.example.com'], $this->config->getDeviceData());
    }
}
