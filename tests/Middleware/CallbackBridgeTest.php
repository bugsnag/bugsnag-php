<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Middleware\CallbackBridge;
use Bugsnag\Report;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class CallbackBridgeTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
    }

    public function testCallback()
    {
        $this->expectOutputString('1reached');

        $middleware = new CallbackBridge(function ($report) {
            echo $report instanceof Report;
        });

        $middleware(Report::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'reached';
        });
    }

    public function testSkips()
    {
        $this->expectOutputString('');

        $middleware = new CallbackBridge(function ($report) {
            return false;
        });

        $middleware(Report::fromPHPThrowable($this->config, new Exception()), function () {
            echo 'reached';
        });
    }

    public function maintainsReportData()
    {
        $report = Report::fromPHPThrowable(
            $this->config,
            new Exception('Oh no')
        );
        $report->setSeverity('error');
        $report->setUnhandled(true);
        $report->setSeverityReason([
            'type' => 'unhandledException',
        ]);

        $middleware = new CallbackBridge(function ($report) {
            $report->setSeverity('info');
            $report->setUnhandled(false);
            $report->setSeverityReason([
                'type' => 'not my',
            ]);
        });

        $middleware($report, function ($report) {
            $this->assertSame('error', $report->getSeverity());
            $this->assertSame(true, $report->getUnhandled());
            $this->assertSame(['type' => 'unhandledException'], $report->getSeverityReason());
        });
    }
}
