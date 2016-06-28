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

    public function testDefaultDeviceData()
    {
        $this->config->hostname = 'web1.example.com';

        $deviceData = $this->diagnostics->getDeviceData();

        $this->assertSame($deviceData['hostname'], 'web1.example.com');
    }
}
