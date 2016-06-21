<?php

<<<<<<< 9e0dd24040bfaf0641d3bed9d259877c5c7db396:tests/Bugsnag/ClientTest.php
use Bugsnag\Client;
=======
namespace Bugsnag\Tests;

use Bugsnag\Client;
use PHPUnit_Framework_TestCase as TestCase;
>>>>>>> PSR-4 refactor:tests/ClientTest.php

class ClientTest extends estCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Bugsnag\Client */
    protected $client;

    protected function setUp()
    {
        // Mock the notify function
        $this->client = $this->getMockBuilder(Client::class)
                             ->setMethods(['notify'])
                             ->setConstructorArgs(['example-api-key'])
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
        if (PHP_MAJOR_VERSION >= 7) {
            $this->setExpectedException('TypeError');
        } else {
            $this->setExpectedException('PHPUnit_Framework_Error');
        }
        $this->client->setCurlOptions('option');
    }
}
