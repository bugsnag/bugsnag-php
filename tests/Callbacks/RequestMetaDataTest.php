<?php

namespace Bugsnag\Tests\Callbacks;

use Bugsnag\Callbacks\RequestMetaData;
use Bugsnag\Configuration;
use Bugsnag\Files\Filesystem;
use Bugsnag\Report;
use Bugsnag\Request\BasicResolver;
use Exception;
use PHPUnit_Framework_TestCase as TestCase;

class RequestMetaDataTest extends TestCase
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

    public function testCanMetaData()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/blah/blah.php?some=param';
        $_SERVER['REMOTE_ADDR'] = '123.45.67.8';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_USER_AGENT'] = 'Example Browser 1.2.3';

        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestMetaData($this->resolver);

        $this->config->setMetaData(['foo' => 'bar']);

        $callback($report);

        $this->assertSame(['bar' => 'baz', 'request' => [
            'url' => 'http://example.com/blah/blah.php?some=param',
            'httpMethod' => 'GET',
            'params' => null,
            'clientIp' => '123.45.67.8',
            'userAgent' => 'Example Browser 1.2.3',
            'headers' => ['Host' => 'example.com', 'User-Agent' => 'Example Browser 1.2.3'],
        ]], $report->getMetaData());
    }

    public function testFallsBackToNull()
    {
        $report = Report::fromPHPThrowable($this->config, $this->filesystem, new Exception())->setMetaData(['bar' => 'baz']);

        $callback = new RequestMetaData($this->resolver);

        $callback($report);

        $this->assertSame(['bar' => 'baz'], $report->getMetaData());
    }
}
