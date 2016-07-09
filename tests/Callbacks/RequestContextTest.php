<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestContext;
use Bugsnag\Configuration;
use Bugsnag\Files\Filesystem;
use Bugsnag\Report;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class RequestContextTest extends TestCase
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

    public function testCanContext()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/blah/blah.php?some=param';

        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception());

        $callback = new RequestContext($this->resolver);

        $callback($report);

        $this->assertSame('PUT /blah/blah.php', $report->getContext());
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception());

        $callback = new RequestContext($this->resolver);

        $callback($report);

        $this->assertSame(null, $report->getContext());
    }

    public function testFallsBackToNull()
    {
        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception());

        $callback = new RequestContext($this->resolver);

        $callback($report);

        $this->assertSame(null, $report->getContext());
    }
}
