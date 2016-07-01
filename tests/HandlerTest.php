<?php

namespace Bugsnag\Tests;

use Bugsnag\Client;
use Bugsnag\Configuration;
use Bugsnag\Handler;
use Exception;
use phpmock\phpunit\PHPMock;
use PHPUnit_Framework_TestCase as TestCase;

class HandlerTest extends TestCase
{
    use PHPMock;

    protected $client;

    protected function setUp()
    {
        $this->client = $this->getMockBuilder(Client::class)
                             ->setMethods(['notify', 'flush'])
                             ->setConstructorArgs([new Configuration('example-api-key')])
                             ->getMock();
    }

    public function testErrorHandler()
    {
        $this->client->expects($this->once())->method('notify');

        Handler::register($this->client)->errorHandler(E_WARNING, 'Something broke', 'somefile.php', 123);
    }

    public function testExceptionHandler()
    {
        $this->client->expects($this->once())->method('notify');

        Handler::register($this->client)->exceptionHandler(new Exception('Something broke'));
    }

    public function testErrorReportingLevel()
    {
        $this->client->expects($this->once())->method('notify');

        $this->client->setErrorReportingLevel(E_NOTICE);

        Handler::register($this->client)->errorHandler(E_NOTICE, 'Something broke', 'somefile.php', 123);
    }

    public function testErrorReportingLevelFails()
    {
        $this->client->expects($this->never())->method('notify');

        $this->client->setErrorReportingLevel(E_NOTICE);

        Handler::register($this->client)->errorHandler(E_WARNING, 'Something broke', 'somefile.php', 123);
    }

    public function testErrorReportingWithoutNotice()
    {
        $this->client->expects($this->never())->method('notify');

        $this->client->setErrorReportingLevel(E_ALL & ~E_NOTICE);

        Handler::register($this->client)->errorHandler(E_NOTICE, 'Something broke', 'somefile.php', 123);
    }

    public function testCanShutdown()
    {
        $this->client->expects($this->never())->method('notify');
        $this->client->expects($this->once())->method('flush');

        Handler::register($this->client)->shutdownHandler();
    }

    public function testCanFatalShutdown()
    {
        $error = $this->getFunctionMock('Bugsnag', 'error_get_last');
        $error->expects($this->once())->will($this->returnValue(['type' => E_ERROR, 'message' => 'Undefined variable: a', 'file' => '/foo/index.php', 'line' => 2]));

        $this->client->expects($this->once())->method('notify');
        $this->client->expects($this->once())->method('flush');

        Handler::register($this->client)->shutdownHandler();
    }
}
