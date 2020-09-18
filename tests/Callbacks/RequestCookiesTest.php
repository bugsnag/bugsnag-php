<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestCookies;
use Bugsnag\Configuration;
use Bugsnag\Report;
use Bugsnag\Request\BasicResolver;
use Bugsnag\Tests\TestCase;
use Exception;

/**
 * @runTestsInSeparateProcesses
 */
class RequestCookiesTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Request\ResolverInterface */
    protected $resolver;

    /**
     * @before
     */
    protected function beforeEach()
    {
        $this->config = new Configuration('API-KEY');
        $this->resolver = new BasicResolver();
    }

    public function testCanCookie()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_COOKIE = ['cookie' => 'cookieval'];

        $report = Report::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestCookies($this->resolver);

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($report);

        $this->assertSame(['bar' => 'baz', 'cookies' => ['cookie' => 'cookieval']], $report->getMetaData());
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $report = Report::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestCookies($this->resolver);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }

    public function testFallsBackToNull()
    {
        $report = Report::fromPHPThrowable($this->config, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestCookies($this->resolver);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }
}
