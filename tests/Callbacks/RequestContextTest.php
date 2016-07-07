<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestContext;
use Bugsnag\Configuration;
use Bugsnag\Report;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class RequestContextTest extends TestCase
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

    public function testCanContext()
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/blah/blah.php?some=param';

        $report = Report::fromPHPThrowable($this->config, new Exception());

        $callback = new RequestContext($this->resolver);

        $callback($report);

        $this->assertSame('PUT /blah/blah.php', $report->getContext());
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $report = Report::fromPHPThrowable($this->config, new Exception());

        $callback = new RequestContext($this->resolver);

        $callback($report);

        $this->assertSame(null, $report->getContext());
    }

    public function testFallsBackToNull()
    {
        $report = Report::fromPHPThrowable($this->config, new Exception());

        $callback = new RequestContext($this->resolver);

        $callback($report);

        $this->assertSame(null, $report->getContext());
    }
}
