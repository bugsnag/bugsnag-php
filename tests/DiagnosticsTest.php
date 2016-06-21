<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Diagnostics;
use PHPUnit_Framework_TestCase as TestCase;

class DiagnosticsTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Diagnostics */
    protected $diagnostics;

    protected function setUp()
    {
        $this->config = new Configuration();
        $this->diagnostics = new Diagnostics($this->config);
    }

    public function testDefaultAppData()
    {
        $this->config->releaseStage = 'qa1';
        $this->config->appVersion = '1.2.3';
        $this->config->type = 'laravel';

        $appData = $this->diagnostics->getAppData();

        $this->assertEquals($appData['releaseStage'], 'qa1');
        $this->assertEquals($appData['version'], '1.2.3');
        $this->assertEquals($appData['type'], 'laravel');
    }

    public function testDefaultDeviceData()
    {
        $this->config->hostname = 'web1.example.com';

        $deviceData = $this->diagnostics->getDeviceData();

        $this->assertEquals($deviceData['hostname'], 'web1.example.com');
    }

    public function testDefaultContext()
    {
        $this->config->context = 'herp#derp';
        $this->assertEquals($this->diagnostics->getContext(), 'herp#derp');
    }

    public function testDefaultUser()
    {
        $this->config->user = ['id' => 123, 'email' => 'test@email.com', 'name' => 'Bob Hoskins'];

        $userData = $this->diagnostics->getUser();

        $this->assertEquals($userData['id'], 123);
        $this->assertEquals($userData['email'], 'test@email.com');
        $this->assertEquals($userData['name'], 'Bob Hoskins');
    }
}
