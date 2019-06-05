<?php

class DiagnosticsTest extends PHPUnit_Framework_TestCase
{
    /** @var Bugsnag_Configuration */
    protected $config;
    /** @var Bugsnag_Diagnostics */
    protected $diagnostics;

    protected function setUp()
    {
        $this->config = new Bugsnag_Configuration();
        $this->diagnostics = new Bugsnag_Diagnostics($this->config);
    }

    public function testDefaultAppData()
    {
        $this->config->releaseStage = 'qa1';
        $this->config->appVersion = '1.2.3';
        $this->config->type = 'laravel';

        $appData = $this->diagnostics->getAppData();

        $this->assertSame('qa1', $appData['releaseStage']);
        $this->assertSame('1.2.3', $appData['version']);
        $this->assertSame('laravel', $appData['type']);
    }

    public function testDefaultDeviceData()
    {
        $this->config->hostname = 'web1.example.com';

        $deviceData = $this->diagnostics->getDeviceData();

        $this->assertEquals(2, count($deviceData));
        $this->assertSame('web1.example.com', $deviceData['hostname']);
        $this->assertSame(phpversion(), $deviceData['runtimeVersions']['php']);
    }

    public function testMergeDeviceDataEmptyArray()
    {
        $newData = array();
        $this->diagnostics->mergeDeviceData($newData);

        $deviceData = $this->diagnostics->getDeviceData();
        $this->assertEquals(2, count($deviceData));
        $this->assertSame(php_uname('n'), $deviceData['hostname']);
        $this->assertSame(phpversion(), $deviceData['runtimeVersions']['php']);
    }

    public function testMergeDeviceDataSingleValue()
    {
        $newData = array('field1' => 'value');
        $this->diagnostics->mergeDeviceData($newData);

        $deviceData = $this->diagnostics->getDeviceData();
        $this->assertEquals(3, count($deviceData));
        $this->assertSame(php_uname('n'), $deviceData['hostname']);
        $this->assertSame(phpversion(), $deviceData['runtimeVersions']['php']);
        $this->assertSame('value', $deviceData['field1']);
    }

    public function testMergeDeviceDataMultiValues()
    {
        $newData = array('field1' => 'value', 'field2' => 2);
        $this->diagnostics->mergeDeviceData($newData);

        $deviceData = $this->diagnostics->getDeviceData();
        $this->assertEquals(4, count($deviceData));
        $this->assertSame(php_uname('n'), $deviceData['hostname']);
        $this->assertSame(phpversion(), $deviceData['runtimeVersions']['php']);
        $this->assertSame('value', $deviceData['field1']);
        $this->assertSame(2, $deviceData['field2']);
    }

    public function testMergeDeviceDataComplexValues()
    {
        $newData = array('array_field' => array(0, 1, 2), 'assoc_array_field' => array('f1' => 1));
        $this->diagnostics->mergeDeviceData($newData);

        $deviceData = $this->diagnostics->getDeviceData();
        $this->assertEquals(4, count($deviceData));
        $this->assertSame(php_uname('n'), $deviceData['hostname']);
        $this->assertSame(phpversion(), $deviceData['runtimeVersions']['php']);
        $this->assertSame(array(0, 1, 2), $deviceData['array_field']);
        $this->assertSame(array('f1' => 1), $deviceData['assoc_array_field']);
    }

    public function testDefaultContext()
    {
        $this->config->context = 'herp#derp';
        $this->assertSame('herp#derp', $this->diagnostics->getContext());
    }

    public function testDefaultUser()
    {
        $this->config->user = array('id' => 123, 'email' => 'test@email.com', 'name' => 'Bob Hoskins');

        $userData = $this->diagnostics->getUser();

        $this->assertSame(123, $userData['id']);
        $this->assertSame('test@email.com', $userData['email']);
        $this->assertSame('Bob Hoskins', $userData['name']);
    }
}
