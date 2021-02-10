<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Configuration;
use Bugsnag\ErrorTypes;
use Bugsnag\Middleware\DiscardClasses;
use Bugsnag\Report;
use Bugsnag\Tests\TestCase;
use Exception;
use LogicException;
use OverflowException;
use UnderflowException;

class DiscardClassesTest extends TestCase
{
    public function testShouldNotifyByDefault()
    {
        $syslog = $this->getFunctionMock('Bugsnag\Middleware', 'syslog');
        $syslog->expects($this->never());

        $config = new Configuration('API-KEY');
        $middleware = new DiscardClasses($config);

        $report = Report::fromPHPThrowable($config, new Exception());
        $this->assertReportIsNotDiscarded($middleware, $report);

        $report = Report::fromPHPThrowable($config, new LogicException());
        $this->assertReportIsNotDiscarded($middleware, $report);
    }

    public function testShouldNotifyWhenExceptionIsNotInDiscardClasses()
    {
        $syslog = $this->getFunctionMock('Bugsnag\Middleware', 'syslog');
        $syslog->expects($this->never());

        $config = new Configuration('API-KEY');
        $config->setDiscardClasses([LogicException::class]);

        $middleware = new DiscardClasses($config);

        $report = Report::fromPHPThrowable($config, new Exception());
        $this->assertReportIsNotDiscarded($middleware, $report);

        $report = Report::fromPHPThrowable($config, new UnderflowException());
        $this->assertReportIsNotDiscarded($middleware, $report);
    }

    public function testShouldNotifyWhenExceptionDoesNotMatchRegex()
    {
        $syslog = $this->getFunctionMock('Bugsnag\Middleware', 'syslog');
        $syslog->expects($this->never());

        $config = new Configuration('API-KEY');
        $config->setDiscardClasses(['/^\d+$/']);

        $middleware = new DiscardClasses($config);

        $report = Report::fromPHPThrowable($config, new Exception());
        $this->assertReportIsNotDiscarded($middleware, $report);

        $report = Report::fromPHPThrowable($config, new LogicException());
        $this->assertReportIsNotDiscarded($middleware, $report);
    }

    public function testShouldDiscardExceptionsThatExactlyMatchADiscardedClass()
    {
        $syslog = $this->getFunctionMock('Bugsnag\Middleware', 'syslog');
        $syslog->expects($this->once())->with(
            LOG_INFO,
            'Discarding event because error class "LogicException" matched discardClasses configuration'
        );

        $config = new Configuration('API-KEY');
        $config->setDiscardClasses([LogicException::class]);

        $middleware = new DiscardClasses($config);

        $report = Report::fromPHPThrowable($config, new LogicException());
        $this->assertReportIsDiscarded($middleware, $report);

        $report = Report::fromPHPThrowable($config, new Exception());
        $this->assertReportIsNotDiscarded($middleware, $report);
    }

    public function testShouldDiscardPreviousExceptionsThatExactlyMatchADiscardedClass()
    {
        $syslog = $this->getFunctionMock('Bugsnag\Middleware', 'syslog');
        $syslog->expects($this->once())->with(
            LOG_INFO,
            'Discarding event because error class "LogicException" matched discardClasses configuration'
        );

        $config = new Configuration('API-KEY');
        $config->setDiscardClasses([LogicException::class]);

        $middleware = new DiscardClasses($config);

        $report = Report::fromPHPThrowable($config, new Exception('', 0, new LogicException()));
        $this->assertReportIsDiscarded($middleware, $report);

        $report = Report::fromPHPThrowable($config, new Exception());
        $this->assertReportIsNotDiscarded($middleware, $report);

        $report = Report::fromPHPThrowable($config, new Exception('', 0, new UnderflowException()));
        $this->assertReportIsNotDiscarded($middleware, $report);
    }

    public function testShouldDiscardExceptionsThatMatchADiscardClassRegex()
    {
        $syslog = $this->getFunctionMock('Bugsnag\Middleware', 'syslog');
        $syslog->expects($this->exactly(3))->withConsecutive(
            [LOG_INFO, 'Discarding event because error class "UnderflowException" matched discardClasses configuration'],
            [LOG_INFO, 'Discarding event because error class "OverflowException" matched discardClasses configuration'],
            [LOG_INFO, 'Discarding event because error class "OverflowException" matched discardClasses configuration']
        );

        $config = new Configuration('API-KEY');
        $config->setDiscardClasses(['/^(Under|Over)flowException$/']);

        $middleware = new DiscardClasses($config);

        $report = Report::fromPHPThrowable($config, new UnderflowException());
        $this->assertReportIsDiscarded($middleware, $report);

        $report = Report::fromPHPThrowable($config, new OverflowException());
        $this->assertReportIsDiscarded($middleware, $report);

        $report = Report::fromPHPThrowable($config, new LogicException());
        $this->assertReportIsNotDiscarded($middleware, $report);

        $report = Report::fromPHPThrowable($config, new LogicException('', 0, new OverflowException()));
        $this->assertReportIsDiscarded($middleware, $report);
    }

    public function testShouldDiscardErrorsThatExactlyMatchAGivenErrorName()
    {
        $syslog = $this->getFunctionMock('Bugsnag\Middleware', 'syslog');
        $syslog->expects($this->once())->with(
            LOG_INFO,
            'Discarding event because error class "PHP Warning" matched discardClasses configuration'
        );

        $config = new Configuration('API-KEY');
        $config->setDiscardClasses([ErrorTypes::getName(E_WARNING)]);

        $middleware = new DiscardClasses($config);

        $report = Report::fromPHPError($config, E_WARNING, 'warning', '/a/b/c.php', 123);
        $this->assertReportIsDiscarded($middleware, $report);

        $report = Report::fromPHPError($config, E_USER_WARNING, 'user warning', '/a/b/c.php', 123);
        $this->assertReportIsNotDiscarded($middleware, $report);
    }

    public function testShouldDiscardErrorsThatMatchARegex()
    {
        $syslog = $this->getFunctionMock('Bugsnag\Middleware', 'syslog');
        $syslog->expects($this->exactly(2))->withConsecutive(
            [LOG_INFO, 'Discarding event because error class "PHP Notice" matched discardClasses configuration'],
            [LOG_INFO, 'Discarding event because error class "User Notice" matched discardClasses configuration']
        );

        $config = new Configuration('API-KEY');
        $config->setDiscardClasses(['/\bNotice\b/']);

        $middleware = new DiscardClasses($config);

        $report = Report::fromPHPError($config, E_NOTICE, 'notice', '/a/b/c.php', 123);
        $this->assertReportIsDiscarded($middleware, $report);

        $report = Report::fromPHPError($config, E_USER_NOTICE, 'user notice', '/a/b/c.php', 123);
        $this->assertReportIsDiscarded($middleware, $report);

        $report = Report::fromPHPError($config, E_WARNING, 'warning', '/a/b/c.php', 123);
        $this->assertReportIsNotDiscarded($middleware, $report);
    }

    /**
     * Assert that DiscardClasses calls the next middleware for this Report.
     *
     * @param DiscardClasses $middleware
     * @param Report $report
     *
     * @return void
     */
    private function assertReportIsNotDiscarded(DiscardClasses $middleware, Report $report)
    {
        $wasCalled = $this->runMiddleware($middleware, $report);

        $this->assertTrue($wasCalled, 'Expected the DiscardClasses middleware to call $next');
    }

    /**
     * Assert that DiscardClasses does not call the next middleware for this Report.
     *
     * @param DiscardClasses $middleware
     * @param Report $report
     *
     * @return void
     */
    private function assertReportIsDiscarded(DiscardClasses $middleware, Report $report)
    {
        $wasCalled = $this->runMiddleware($middleware, $report);

        $this->assertFalse($wasCalled, 'Expected the DiscardClasses middleware not to call $next');
    }

    /**
     * Run the given middleware against the report and return if it called $next.
     *
     * @param callable $middleware
     * @param Report $report
     *
     * @return bool
     */
    private function runMiddleware(callable $middleware, Report $report)
    {
        $wasCalled = false;

        $middleware($report, function () use (&$wasCalled) {
            $wasCalled = true;
        });

        return $wasCalled;
    }
}
