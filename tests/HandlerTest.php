<?php

namespace Bugsnag\Tests;

use Bugsnag\Client;
use Bugsnag\Configuration;
use Bugsnag\Handler;
use Closure;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

class HandlerTest extends TestCase
{
    /**
     * @var Client&MockObject
     */
    protected $client;

    /**
     * The original error reporting level before each test run. This is used to
     * restore the error reporting level after each test.
     *
     * @var int
     */
    protected $originalErrorReporting;

    /**
     * @before
     */
    protected function beforeEach()
    {
        $this->client = $this->getMockBuilder(Client::class)
            ->setMethods(['notify', 'flush'])
            ->setConstructorArgs([new Configuration('example-api-key')])
            ->getMock();

        $this->originalErrorReporting = error_reporting();
    }

    /**
     * @after
     */
    protected function afterEach()
    {
        error_reporting($this->originalErrorReporting);
    }

    public function testErrorHandler()
    {
        $this->runErrorHandlerTest(function () {
            $this->client->expects($this->once())->method('notify');

            $handler = Handler::register($this->client);
            $handler->errorHandler(E_WARNING, 'Something broke', 'somefile.php', 123);
        });
    }

    public function testErrorHandlerWithPrevious()
    {
        $this->runErrorHandlerTest(function () {
            $this->client->expects($this->once())->method('notify');

            $handler = Handler::registerWithPrevious($this->client);
            $handler->errorHandler(E_WARNING, 'Something broke', 'somefile.php', 123);
        });
    }

    public function testItReturnsThePreviousErrorHandlerReturnValue()
    {
        $this->runErrorHandlerTest(function () {
            $previousHandler = set_error_handler(function () use (&$previousHandler) {
                $previousHandler();

                return '123';
            });

            $this->client->expects($this->once())->method('notify');
            $handler = Handler::registerWithPrevious($this->client);

            $this->assertSame(
                '123',
                $handler->errorHandler(E_WARNING, 'Something broke')
            );
        });
    }

    public function testErrorReportingLevel()
    {
        $this->runErrorHandlerTest(function () {
            $this->client->expects($this->once())->method('notify');
            $this->client->setErrorReportingLevel(E_NOTICE);

            $handler = Handler::register($this->client);
            $handler->errorHandler(E_NOTICE, 'Something broke', 'somefile.php', 123);
        });
    }

    public function testErrorReportingLevelFails()
    {
        $this->runErrorHandlerTest(function () {
            $this->client->expects($this->never())->method('notify');
            $this->client->setErrorReportingLevel(E_ALL & ~E_WARNING);

            $handler = Handler::register($this->client);
            $handler->errorHandler(E_WARNING, 'Something broke', 'somefile.php', 123);
        });
    }

    public function testErrorReportingDefaultFails()
    {
        $this->runErrorHandlerTest(function () {
            error_reporting(E_NOTICE);

            $this->client->expects($this->never())->method('notify');

            $handler = Handler::register($this->client);
            $handler->errorHandler(E_WARNING, 'Something broke', 'somefile.php', 123);
        });
    }

    public function testErrorReportingSuppressed()
    {
        $this->runErrorHandlerTest(function () {
            error_reporting(0);

            $this->client->setErrorReportingLevel(E_NOTICE);
            $this->client->expects($this->never())->method('notify');

            $handler = Handler::register($this->client);
            $handler->errorHandler(E_NOTICE, 'Something broke', 'somefile.php', 123);
        });
    }

    public function testErrorReportingDefaultSuppressed()
    {
        $this->runErrorHandlerTest(function () {
            error_reporting(0);

            $this->client->expects($this->never())->method('notify');

            $handler = Handler::register($this->client);
            $handler->errorHandler(E_NOTICE, 'Something broke', 'somefile.php', 123);
        });
    }

    public function testExceptionHandler()
    {
        $this->runExceptionHandlerTest(function () {
            $this->client->expects($this->once())->method('notify');

            $handler = Handler::register($this->client);
            $handler->exceptionHandler(new Exception('Something broke'));
        });
    }

    public function testCanShutdown()
    {
        $this->client->expects($this->never())->method('notify');
        $this->client->expects($this->once())->method('flush');

        $handler = Handler::register($this->client);
        $handler->shutdownHandler();
    }

    /**
     * @runInSeparateProcess
     */
    public function testCanFatalShutdown()
    {
        $report = $this->getFunctionMock('Bugsnag', 'error_get_last');
        $report->expects($this->once())->will($this->returnValue(['type' => E_ERROR, 'message' => 'Undefined variable: a', 'file' => '/foo/index.php', 'line' => 2]));

        $this->client->expects($this->once())->method('notify');
        $this->client->expects($this->once())->method('flush');

        $handler = Handler::register($this->client);
        $handler->shutdownHandler();
    }

    /**
     * @param Closure $test
     *
     * @return void
     */
    private function runErrorHandlerTest($test)
    {
        $this->runHandlerTest(
            $test,
            'set_error_handler',
            'restore_error_handler'
        );
    }

    /**
     * @param Closure $test
     *
     * @return void
     */
    private function runExceptionHandlerTest($test)
    {
        $this->runHandlerTest(
            $test,
            'set_exception_handler',
            'restore_exception_handler'
        );
    }

    /**
     * Don't call this directly! Use {@see runErrorHandlerTest} or
     * {@see runExceptionHandlerTest} instead.
     *
     * @param Closure $test
     * @param callable $setHandler
     * @param callable $restoreHandler
     *
     * @return void
     */
    private function runHandlerTest($test, $setHandler, $restoreHandler)
    {
        try {
            $handlerWasCalled = false;

            $setHandler(function () use (&$handlerWasCalled) {
                $handlerWasCalled = true;
            });

            $this->assertFalse(
                $handlerWasCalled,
                'Expected the previous handler not to be called before running the test'
            );

            $test();
        } finally {
            $restoreHandler();
        }
    }
}
