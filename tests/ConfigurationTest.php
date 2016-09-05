<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use PHPUnit_Framework_TestCase as TestCase;

class ConfigurationTest extends TestCase
{
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
        $this->assertSame('Bugsnag PHP (Official)', $this->config->getNotifier()['name']);
        $this->assertSame('https://bugsnag.com', $this->config->getNotifier()['url']);

        $this->config->setNotifier(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $this->config->getNotifier());
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

    public function testRootPath()
    {
        $this->assertFalse($this->config->isInProject(__FILE__));

        $this->config->setProjectRoot(__DIR__);

        $this->assertTrue($this->config->isInProject(__FILE__));
        $this->assertFalse($this->config->isInProject(dirname(__DIR__)));
    }

    public function testAppData()
    {
        $this->assertSame(['type' => 'cli', 'releaseStage' => 'production'], $this->config->getAppData());

        $this->config->setReleaseStage('qa1');
        $this->config->setAppVersion('1.2.3');
        $this->config->setAppType('laravel');

        $this->assertSame(['type' => 'laravel', 'releaseStage' => 'qa1', 'version' => '1.2.3'], $this->config->getAppData());

        $this->config->setAppType(null);

        $this->assertSame(['type' => 'cli', 'releaseStage' => 'qa1', 'version' => '1.2.3'], $this->config->getAppData());

        $this->config->setFallbackType('foo');

        $this->assertSame(['type' => 'foo', 'releaseStage' => 'qa1', 'version' => '1.2.3'], $this->config->getAppData());

        $this->config->setReleaseStage(null);

        $this->assertSame(['type' => 'foo', 'releaseStage' => 'production', 'version' => '1.2.3'], $this->config->getAppData());

        $this->config->setAppVersion(null);

        $this->assertSame(['type' => 'foo', 'releaseStage' => 'production'], $this->config->getAppData());

        $this->config->setFallbackType(null);

        $this->assertSame(['releaseStage' => 'production'], $this->config->getAppData());
    }

    public function testDeviceData()
    {
        $this->assertSame(['hostname' => php_uname('n')], $this->config->getDeviceData());

        $this->config->setHostname('web1.example.com');

        $this->assertSame(['hostname' => 'web1.example.com'], $this->config->getDeviceData());
    }
}
