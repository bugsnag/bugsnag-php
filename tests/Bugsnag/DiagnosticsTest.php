<?php

class DiagnosticsTest extends PHPUnit_Framework_TestCase {
    protected $config;

    protected function setUp(){
        $this->config = new Bugsnag_Configuration();
        $this->diagnostics = new Bugsnag_Diagnostics($this->config);
    }

    public function testDefaultAppData() {
        $this->config->releaseStage = 'qa1';
        $this->config->appVersion = '1.2.3';
        $this->config->type = "laravel";

        $this->assertEquals($this->diagnostics->getAppData()['releaseStage'], 'qa1');
        $this->assertEquals($this->diagnostics->getAppData()['version'], '1.2.3');
        $this->assertEquals($this->diagnostics->getAppData()['type'], 'laravel');
    }

    public function testDefaultDeviceData() {
        $this->config->hostname = 'web1.example.com';
        $this->assertEquals($this->diagnostics->getDeviceData()['hostname'], 'web1.example.com');
    }

    public function testDefaultContext() {
        $this->config->context = 'herp#derp';
        $this->assertEquals($this->diagnostics->getContext(), 'herp#derp');
    }

    public function testDefaultUser() {
        $this->config->user = array('id' => 123, 'email' => "test@email.com", 'name' => "Bob Hoskins");
        $this->assertEquals($this->diagnostics->getUser()['id'], 123);
        $this->assertEquals($this->diagnostics->getUser()['email'], "test@email.com");
        $this->assertEquals($this->diagnostics->getUser()['name'], "Bob Hoskins");
    }
}
