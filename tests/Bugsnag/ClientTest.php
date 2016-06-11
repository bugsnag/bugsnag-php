<?php

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

if (PHP_VERSION_ID < 50207) {
    define('PHP_MAJOR_VERSION',   $version[0]);
    define('PHP_MINOR_VERSION',   $version[1]);
    define('PHP_RELEASE_VERSION', $version[2]);
}

class ClientTest extends PHPUnit_Framework_TestCase
{
    /** @var PHPUnit_Framework_MockObject_MockObject|Bugsnag_Client */
    protected $client;

    protected function setUp()
    {
        // Mock the notify function
        $this->client = $this->getMockBuilder('Bugsnag_Client')
                             ->setMethods(array('notify'))
                             ->setConstructorArgs(array('example-api-key'))
                             ->getMock();
    }

    public function testConstructThrowsWhenConfigHasNoApiKey()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Bugsnag_Client(new Bugsnag_Configuration());
    }

    public function testConstructThrowsWhenConfigNotStringNorConfig()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Bugsnag_Client(array());
    }

    public function testConstructThrowsWhenDiagnosticsIsSetWithoutConfig()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Bugsnag_Client('api-key', new Bugsnag_Diagnostics(new Bugsnag_Configuration()));
    }

    public function testConstructWithApiKey()
    {
        if (PHP_VERSION_ID < 50300) {
            $this->markTestSkipped('ReflectionProperty::setAccessible() is not available on PHP ' . PHP_VERSION);
        }

        $client = new Bugsnag_Client('api-key');
        $config = $this->getNotAccessibleProperty($client, 'config');
        $this->assertEquals('api-key', $config->apiKey);
        $this->assertNotNull($this->getNotAccessibleProperty($client, 'diagnostics'));
    }

    public function testConstructWithConfigurationInstance()
    {
        if (PHP_VERSION_ID < 50300) {
            $this->markTestSkipped('ReflectionProperty::setAccessible() is not available on PHP ' . PHP_VERSION);
        }

        $config = new Bugsnag_Configuration();
        $config->apiKey = 'api-key';
        $client = new Bugsnag_Client($config);
        $clientConfig = $this->getNotAccessibleProperty($client, 'config');
        $this->assertEquals($config, $clientConfig);
        $this->assertNotNull($this->getNotAccessibleProperty($client, 'diagnostics'));
    }

    public function testConstructWithConfigurationInstanceAndDiagnostics()
    {
        if (PHP_VERSION_ID < 50300) {
            $this->markTestSkipped('ReflectionProperty::setAccessible() is not available on PHP ' . PHP_VERSION);
        }

        $config = new Bugsnag_Configuration();
        $config->apiKey = 'api-key';
        $diagnostics = new Bugsnag_Diagnostics($config);
        $client = new Bugsnag_Client($config, $diagnostics);
        $clientConfig = $this->getNotAccessibleProperty($client, 'config');
        $clientDiagnostics = $this->getNotAccessibleProperty($client, 'diagnostics');
        $this->assertEquals($config, $clientConfig);
        $this->assertEquals($clientDiagnostics, $diagnostics);
    }

    public function testErrorHandler()
    {
        $this->client->expects($this->once())
                     ->method('notify');

        $this->client->errorHandler(E_WARNING, "Something broke", "somefile.php", 123);
    }

    public function testExceptionHandler()
    {
        $this->client->expects($this->once())
                     ->method('notify');

        $this->client->exceptionHandler(new Exception("Something broke"));
    }

    public function testManualErrorNotification()
    {
        $this->client->expects($this->once())
                     ->method('notify');

        $this->client->notifyError("SomeError", "Some message");
    }

    public function testManualExceptionNotification()
    {
        $this->client->expects($this->once())
                     ->method('notify');

        $this->client->notifyException(new Exception("Something broke"));
    }

    public function testErrorReportingLevel()
    {
        $this->client->expects($this->once())
                     ->method('notify');

        $this->client->setErrorReportingLevel(E_NOTICE)
                     ->errorHandler(E_NOTICE, "Something broke", "somefile.php", 123);
    }

    public function testErrorReportingLevelFails()
    {
        $this->client->expects($this->never())
                     ->method('notify');

        $this->client->setErrorReportingLevel(E_NOTICE)
                     ->errorHandler(E_WARNING, "Something broke", "somefile.php", 123);
    }

    public function testErrorReportingWithoutNotice()
    {
        $this->client->expects($this->never())
                     ->method('notify');

        $this->client->setErrorReportingLevel(E_ALL & ~E_NOTICE)
                     ->errorHandler(E_NOTICE, "Something broke", "somefile.php", 123);
    }

    public function testSetInvalidCurlOptions()
    {
		if (PHP_MAJOR_VERSION >= 7) {
            $this->setExpectedException('TypeError');
        } else {
            $this->setExpectedException('PHPUnit_Framework_Error');
        }
        $this->client->setCurlOptions("option");
    }

    /**
     * @param object $object
     * @param string $propertyName
     * @return mixed
     */
    protected function getNotAccessibleProperty($object, $propertyName)
    {
        $class = new \ReflectionClass($object);
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
