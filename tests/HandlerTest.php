<?php

namespace Bugsnag\Tests;

use Bugsnag\Client;
use Bugsnag\Configuration;
use Bugsnag\Handler;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class HandlerTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Bugsnag\Client */
    protected $client;

    protected function setUp()
    {
        // Mock the notify function
        $this->client = $this->getMockBuilder(Client::class)
                             ->setMethods(['notify'])
                             ->setConstructorArgs([new Configuration('example-api-key')])
                             ->getMock();
    }

    /**
     * @runTestsInSeparateProcesses
     */
    public function testErrorHandler()
    {
        $this->client->expects($this->once())->method('notify');

        Handler::register($this->client)->errorHandler(E_WARNING, 'Something broke', 'somefile.php', 123);
    }

    /**
     * @runTestsInSeparateProcesses
     */
    public function testExceptionHandler()
    {
        $this->client->expects($this->once())->method('notify');

        Handler::register($this->client)->exceptionHandler(new Exception('Something broke'));
    }

    /**
     * @runTestsInSeparateProcesses
     */
    public function testErrorReportingLevel()
    {
        $this->client->expects($this->once())->method('notify');

        $this->client->setErrorReportingLevel(E_NOTICE);

        Handler::register($this->client)->errorHandler(E_NOTICE, 'Something broke', 'somefile.php', 123);
    }

    /**
     * @runTestsInSeparateProcesses
     */
    public function testErrorReportingLevelFails()
    {
        $this->client->expects($this->never())->method('notify');

        $this->client->setErrorReportingLevel(E_NOTICE);
        
        Handler::register($this->client)->errorHandler(E_WARNING, 'Something broke', 'somefile.php', 123);
    }

    /**
     * @runTestsInSeparateProcesses
     */
    public function testErrorReportingWithoutNotice()
    {
        $this->client->expects($this->never())->method('notify');

        $this->client->setErrorReportingLevel(E_ALL & ~E_NOTICE);
        
        Handler::register($this->client)->errorHandler(E_NOTICE, 'Something broke', 'somefile.php', 123);
    }
}
