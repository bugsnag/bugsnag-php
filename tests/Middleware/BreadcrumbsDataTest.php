<?php

namespace Bugsnag\Tests\Middleware;

use Bugsnag\Breadcrumbs\Breadcrumb;
use Bugsnag\Breadcrumbs\Recorder;
use Bugsnag\Configuration;
use Bugsnag\Middleware\BreadcrumbData;
use Bugsnag\Report;
use Bugsnag\Tests\Assert;
use Bugsnag\Tests\TestCase;
use Exception;

class BreadcrumbsDataTest extends TestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;

    /** @var \Bugsnag\Breadcrumbs\Recorder */
    protected $recorder;

    /**
     * @before
     */
    protected function beforeEach()
    {
        $this->config = new Configuration('API-KEY');
        $this->recorder = new Recorder();
    }

    public function testWithoutBreadcrumbs()
    {
        $breadcrumbs = null;
        $middleware = new BreadcrumbData($this->recorder);

        $middleware(Report::fromPHPThrowable($this->config, new Exception()), function (Report $report) use (&$breadcrumbs) {
            $breadcrumbs = $report->toArray()['breadcrumbs'];
        });

        $this->assertSame([], $breadcrumbs);
    }

    public function testBasicBreadcrumb()
    {
        $breadcrumbs = null;
        $middleware = new BreadcrumbData($this->recorder);

        $this->recorder->record(new Breadcrumb('Foo', 'error'));

        $middleware(Report::fromPHPThrowable($this->config, new Exception()), function (Report $report) use (&$breadcrumbs) {
            $breadcrumbs = $report->toArray()['breadcrumbs'];
        });

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(3, $breadcrumbs[0]);
        Assert::isType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Foo', $breadcrumbs[0]['name']);
        $this->assertSame('error', $breadcrumbs[0]['type']);
        $this->assertFalse(isset($breadcrumbs[0]['metaData']));
    }

    public function testBreadcrumbWithMetaData()
    {
        $breadcrumbs = null;
        $middleware = new BreadcrumbData($this->recorder);

        $this->recorder->record(new Breadcrumb('Foo', 'error', ['foo' => 'bar']));

        $middleware(Report::fromPHPThrowable($this->config, new Exception()), function (Report $report) use (&$breadcrumbs) {
            $breadcrumbs = $report->toArray()['breadcrumbs'];
        });

        $this->assertCount(1, $breadcrumbs);

        $this->assertCount(4, $breadcrumbs[0]);
        Assert::isType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Foo', $breadcrumbs[0]['name']);
        $this->assertSame('error', $breadcrumbs[0]['type']);
        $this->assertSame(['foo' => 'bar'], $breadcrumbs[0]['metaData']);
    }

    public function testTwoBreadcrumbs()
    {
        $breadcrumbs = null;
        $middleware = new BreadcrumbData($this->recorder);

        $this->recorder->record(new Breadcrumb('Foo', 'error', ['foo' => 'bar']));
        $this->recorder->record(new Breadcrumb('Bar', 'log'));

        $middleware(Report::fromPHPThrowable($this->config, new Exception()), function (Report $report) use (&$breadcrumbs) {
            $breadcrumbs = $report->toArray()['breadcrumbs'];
        });

        $this->assertCount(2, $breadcrumbs);

        $this->assertCount(4, $breadcrumbs[0]);
        Assert::isType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Foo', $breadcrumbs[0]['name']);
        $this->assertSame('error', $breadcrumbs[0]['type']);
        $this->assertSame(['foo' => 'bar'], $breadcrumbs[0]['metaData']);

        $this->assertCount(3, $breadcrumbs[1]);
        Assert::isType('string', $breadcrumbs[1]['timestamp']);
        $this->assertSame('Bar', $breadcrumbs[1]['name']);
        $this->assertSame('log', $breadcrumbs[1]['type']);
        $this->assertFalse(isset($breadcrumbs[1]['metaData']));
    }

    public function testManyBreadcrumbs()
    {
        $breadcrumbs = null;
        $middleware = new BreadcrumbData($this->recorder);
        $this->recorder->setMaxBreadcrumbs(25);

        $this->recorder->record(new Breadcrumb('Foo', 'error', ['foo' => 'bar']));

        for ($i = 0; $i < 30; $i++) {
            $this->recorder->record(new Breadcrumb('Bar', 'log'));
        }

        $this->recorder->record(new Breadcrumb('Baz', 'navigation', ['baz' => 'bar']));

        $middleware(Report::fromPHPThrowable($this->config, new Exception()), function (Report $report) use (&$breadcrumbs) {
            $breadcrumbs = $report->toArray()['breadcrumbs'];
        });

        $this->assertCount(25, $breadcrumbs);

        $this->assertCount(3, $breadcrumbs[0]);
        Assert::isType('string', $breadcrumbs[0]['timestamp']);
        $this->assertSame('Bar', $breadcrumbs[0]['name']);
        $this->assertSame('log', $breadcrumbs[0]['type']);
        $this->assertFalse(isset($breadcrumbs[0]['metaData']));

        $this->assertCount(4, $breadcrumbs[24]);
        Assert::isType('string', $breadcrumbs[24]['timestamp']);
        $this->assertSame('Baz', $breadcrumbs[24]['name']);
        $this->assertSame('navigation', $breadcrumbs[24]['type']);
        $this->assertSame(['baz' => 'bar'], $breadcrumbs[24]['metaData']);
    }
}
