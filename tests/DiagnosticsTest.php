<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Diagnostics;
use Bugsnag\Request\BasicResolver;
use PHPUnit_Framework_TestCase as TestCase;

class DiagnosticsTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Request\ResolverInterface */
    protected $resolver;
    /** @var \Bugsnag\Diagnostics */
    protected $diagnostics;

    protected function setUp()
    {
        $this->config = new Configuration('example-key');
        $this->resolver = new BasicResolver();
        $this->diagnostics = new Diagnostics($this->config, $this->resolver);
    }

    public function testDefaultAppData()
    {
        $this->config->releaseStage = 'qa1';
        $this->config->appVersion = '1.2.3';
        $this->config->type = 'laravel';

        $appData = $this->diagnostics->getAppData();

        $this->assertSame($appData['releaseStage'], 'qa1');
        $this->assertSame($appData['version'], '1.2.3');
        $this->assertSame($appData['type'], 'laravel');
    }

    public function testDefaultDeviceData()
    {
        $this->config->hostname = 'web1.example.com';

        $deviceData = $this->diagnostics->getDeviceData();

        $this->assertSame($deviceData['hostname'], 'web1.example.com');
    }
}
