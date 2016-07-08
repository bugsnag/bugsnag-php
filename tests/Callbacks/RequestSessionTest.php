<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestSession;
use Bugsnag\Configuration;
use Bugsnag\Files\Filesystem;
use Bugsnag\Report;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class RequestSessionTest extends TestCase
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

    public function testCanSession()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION = ['session' => 'sessionval'];

        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestSession($this->resolver);

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($report);

        $this->assertSame(['bar' => 'baz', 'session' => ['session' => 'sessionval']], $report->getMetaData());
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestSession($this->resolver);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }

    public function testFallsBackToNull()
    {
        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestSession($this->resolver);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }
}
