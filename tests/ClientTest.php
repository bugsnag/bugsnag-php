<?php

namespace Bugsnag\Tests;

use Bugsnag\Client;
use Exception;
use PHPUnit_Framework_Error as PHPUnitError;
use PHPUnit_Framework_TestCase as TestCase;
use TypeError;

class ClientTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Bugsnag\Client */
    protected $client;

    protected function setUp()
    {
        // Mock the notify function
        $this->client = $this->getMockBuilder(Client::class)
                             ->setMethods(['notify'])
                             ->setConstructorArgs([new Confiugration('example-api-key')])
                             ->getMock();
    }

    public function testErrorHandler()
    {
        $this->client->expects($this->once())
                     ->method('notify');

        $this->client->errorHandler(E_WARNING, 'Something broke', 'somefile.php', 123);
    }

    public function testExceptionHandler()
    {
        $this->client->expects($this->once())
                     ->method('notify');

        $this->client->exceptionHandler(new Exception('Something broke'));
    }

    public function testManualErrorNotification()
    {
        $this->client->expects($this->once())
                     ->method('notify');

        $this->client->notifyError('SomeError', 'Some message');
    }

    public function testManualExceptionNotification()
    {
        $this->client->expects($this->once())
                     ->method('notify');

        $this->client->notifyException(new Exception('Something broke'));
    }

    public function testErrorReportingLevel()
    {
        $this->client->expects($this->once())
                     ->method('notify');

        $this->client->setErrorReportingLevel(E_NOTICE)
                     ->errorHandler(E_NOTICE, 'Something broke', 'somefile.php', 123);
    }

    public function testErrorReportingLevelFails()
    {
        $this->client->expects($this->never())
                     ->method('notify');

        $this->client->setErrorReportingLevel(E_NOTICE)
                     ->errorHandler(E_WARNING, 'Something broke', 'somefile.php', 123);
    }

    public function testErrorReportingWithoutNotice()
    {
        $this->client->expects($this->never())
                     ->method('notify');

        $this->client->setErrorReportingLevel(E_ALL & ~E_NOTICE)
                     ->errorHandler(E_NOTICE, 'Something broke', 'somefile.php', 123);
    }

    public function testSetInvalidCurlOptions()
    {
        if (class_exists(TypeError::class)) {
            $this->setExpectedException(TypeError::class);
        } else {
            $this->setExpectedException(PHPUnitError::class);
        }
        $this->client->setCurlOptions('option');
    }
}
