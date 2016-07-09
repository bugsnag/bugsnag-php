<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestCookies;
use Bugsnag\Configuration;
use Bugsnag\Files\Filesystem;
use Bugsnag\Report;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class RequestCookiesTest extends TestCase
{
    protected $config;
    protected $resolver;
    protected $filesystem;

    protected function setUp()
    {
        $this->config = new Configuration('API-KEY');
        $this->resolver = new BasicResolver();
        $this->filesystem = new Filesystem();
    }

    public function testCanCookie()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_COOKIE = ['cookie' => 'cookieval'];

        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestCookies($this->resolver);

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($report);

        $this->assertSame(['bar' => 'baz', 'cookies' => ['cookie' => 'cookieval']], $report->getMetaData());
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestCookies($this->resolver);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }

    public function testFallsBackToNull()
    {
        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestCookies($this->resolver);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }
}
