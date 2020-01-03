<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestUser;
use Bugsnag\Configuration;
use Bugsnag\Report;
use Bugsnag\Request\BasicResolver;
use Bugsnag\Tests\TestCase;
use Exception;

class RequestUserTest extends TestCase
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

    public function testCanUser()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';

        $report = Report::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $callback = new RequestUser($this->resolver);

        $callback($report);

        $this->assertSame(['id' => '123.45.67.8'], $report->getUser());
    }

    public function testCanForwardedUser()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '321.42.42.42';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';

        $report = Report::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $callback = new RequestUser($this->resolver);

        $callback($report);

        $this->assertSame(['id' => '321.42.42.42'], $report->getUser());
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $report = Report::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $callback = new RequestUser($this->resolver);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getUser());
    }

    public function testFallsBackToNull()
    {
        $report = Report::fromPHPThrowable($this->config, new Exception())->setUser(['bar' => 'baz']);

        $callback = new RequestUser($this->resolver);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getUser());
    }
}
