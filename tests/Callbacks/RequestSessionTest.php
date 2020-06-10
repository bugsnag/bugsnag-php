<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestSession;
use Bugsnag\Configuration;
use Bugsnag\Report;
use Bugsnag\Request\BasicResolver;
use Bugsnag\Tests\TestCase;
use Exception;

/**
 * @runTestsInSeparateProcesses
 */
class RequestSessionTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Request\ResolverInterface */
    protected $resolver;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
        $this->resolver = new BasicResolver();
    }

    public function testCanSession()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION = ['session' => 'sessionval'];

        $report = Report::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestSession($this->resolver);

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($report);

        $this->assertSame(['bar' => 'baz', 'session' => ['session' => 'sessionval']], $report->getMetaData());
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $report = Report::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestSession($this->resolver);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }

    public function testFallsBackToNull()
    {
        $report = Report::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestSession($this->resolver);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }
}
