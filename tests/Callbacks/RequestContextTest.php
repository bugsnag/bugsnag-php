<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestContext;
use Bugsnag\Configuration;
use Bugsnag\Error;
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

        $error = Error::fromPHPThrowable($this->config, new Exception());

        $callback = new RequestContext($this->resolver);

        $callback($error);

        $this->assertSame('PUT /blah/blah.php', $error->getContext());
    }

    public function testCanDoNothing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $error = Error::fromPHPThrowable($this->config, new Exception());

        $callback = new RequestContext($this->resolver);

        $callback($error);

        $this->assertSame(null, $error->getContext());
    }

    public function testFallsBackToNull()
    {
        $error = Error::fromPHPThrowable($this->config, new Exception());

        $callback = new RequestContext($this->resolver);

        $callback($error);

        $this->assertSame(null, $error->getContext());
    }
}
