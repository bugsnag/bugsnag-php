<?php

namespace Bugsnag\Tests\Breadcrumbs;

use Bugsnag\Breadcrumbs\Breadcrumb;
use Bugsnag\Breadcrumbs\Recorder;
use Bugsnag\Tests\TestCase;
use Countable;
use Iterator;
use stdClass;

class RecorderTest extends TestCase
{
    public function testItImplementsIteratorAndCountable()
    {
        $recorder = new Recorder();

        $this->assertInstanceOf(Iterator::class, $recorder);
        $this->assertInstanceOf(Countable::class, $recorder);
    }

    public function testItCanBeIterated()
    {
        $breadcrumbs = [
            new Breadcrumb('one', 'error'),
            new Breadcrumb('two', 'user'),
            new Breadcrumb('three', 'user'),
            new Breadcrumb('four', 'user'),
        ];

        $recorder = new Recorder();
        $recorder->record($breadcrumbs[0]);
        $recorder->record($breadcrumbs[1]);
        $recorder->record($breadcrumbs[2]);
        $recorder->record($breadcrumbs[3]);

        foreach ($recorder as $i => $breadcrumb) {
            $this->assertSame($breadcrumbs[$i], $breadcrumb);
        }

        $this->assertSame($breadcrumbs, iterator_to_array($recorder));
    }

    public function testNoneRecorded()
    {
        $recorder = new Recorder();

        $this->assertCount(0, $recorder);
        $this->assertSame([], iterator_to_array($recorder));

        $recorder->clear();

        $this->assertCount(0, $recorder);
        $this->assertSame([], iterator_to_array($recorder));
    }

    public function testOneRecorded()
    {
        $recorder = new Recorder();
        $breadcrumb = new Breadcrumb('Foo', 'error');

        $recorder->record($breadcrumb);

        $this->assertCount(1, $recorder);
        $this->assertSame([$breadcrumb], iterator_to_array($recorder));

        $recorder->clear();

        $this->assertCount(0, $recorder);
        $this->assertSame([], iterator_to_array($recorder));
    }

    public function testTwoRecorded()
    {
        $recorder = new Recorder();
        $one = new Breadcrumb('Foo', 'error');
        $two = new Breadcrumb('Bar', 'user');

        $recorder->record($one);
        $recorder->record($two);

        $this->assertCount(2, $recorder);
        $this->assertSame([$one, $two], iterator_to_array($recorder));

        $recorder->clear();

        $this->assertCount(0, $recorder);
        $this->assertSame([], iterator_to_array($recorder));
    }

    public function testManyRecorded()
    {
        $recorder = new Recorder();
        $recorder->setMaxBreadcrumbs(25);

        $one = new Breadcrumb('Foo', 'error');
        $two = new Breadcrumb('Bar', 'user');
        $three = new Breadcrumb('Baz', 'request');

        $recorder->record($one);

        for ($i = 0; $i < 30; $i++) {
            $recorder->record($two);
        }

        $recorder->record($three);
        $recorder->record($two);
        $recorder->record($three);

        $this->assertCount(25, $recorder);
        $this->assertSame([$two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $three, $two, $three], iterator_to_array($recorder));

        $recorder->clear();

        $this->assertCount(0, $recorder);
        $this->assertSame([], iterator_to_array($recorder));
    }

    public function testItCanGrow()
    {
        $recorder = new Recorder();
        $recorder->setMaxBreadcrumbs(2);

        $one = new Breadcrumb('one', 'error');
        $two = new Breadcrumb('two', 'user');
        $three = new Breadcrumb('three', 'user');
        $four = new Breadcrumb('four', 'user');

        $recorder->record($one);
        $recorder->record($two);
        $recorder->record($three);
        $recorder->record($four);

        $this->assertSame([$three, $four], iterator_to_array($recorder));

        $recorder->setMaxBreadcrumbs(4);

        $recorder->record($one);
        $recorder->record($two);

        $this->assertSame([$three, $four, $one, $two], iterator_to_array($recorder));

        $recorder->record($three);
        $recorder->record($four);

        $this->assertSame([$one, $two, $three, $four], iterator_to_array($recorder));
    }

    public function testItCanShrink()
    {
        $recorder = new Recorder();
        $recorder->setMaxBreadcrumbs(4);

        $one = new Breadcrumb('one', 'error');
        $two = new Breadcrumb('two', 'user');

        $recorder->record($one);
        $recorder->record($two);
        $recorder->record($one);
        $recorder->record($two);

        $this->assertSame([$one, $two, $one, $two], iterator_to_array($recorder));

        $recorder->setMaxBreadcrumbs(2);

        $this->assertSame([$one, $two], iterator_to_array($recorder));

        $recorder->record($two);
        $recorder->record($one);

        $this->assertSame([$two, $one], iterator_to_array($recorder));

        $recorder->setMaxBreadcrumbs(0);

        $this->assertSame([], iterator_to_array($recorder));

        $recorder->record($two);

        $this->assertSame([], iterator_to_array($recorder));
    }

    public function testItDoesNotAllowNegativeMaxes()
    {
        $log = $this->getFunctionMock('Bugsnag\Breadcrumbs', 'error_log');
        $log->expects($this->once())
            ->with($this->equalTo(
                'Bugsnag Warning: maxBreadcrumbs should be an integer between 0 and 100 (inclusive)'
            ));

        $recorder = new Recorder();

        $previousMax = $recorder->getMaxBreadcrumbs();

        $recorder->setMaxBreadcrumbs(-1);

        $this->assertNotSame(-1, $recorder->getMaxBreadcrumbs());
        $this->assertSame($previousMax, $recorder->getMaxBreadcrumbs());
    }

    public function testItDoesNotAllowMaxesGreaterThan100()
    {
        $log = $this->getFunctionMock('Bugsnag\Breadcrumbs', 'error_log');
        $log->expects($this->once())
            ->with($this->equalTo(
                'Bugsnag Warning: maxBreadcrumbs should be an integer between 0 and 100 (inclusive)'
            ));

        $recorder = new Recorder();

        $previousMax = $recorder->getMaxBreadcrumbs();
        $recorder->setMaxBreadcrumbs(101);

        $this->assertNotSame(101, $recorder->getMaxBreadcrumbs());
        $this->assertSame($previousMax, $recorder->getMaxBreadcrumbs());
    }

    public function testItDoesNotAllowNonIntegerMaxes()
    {
        $log = $this->getFunctionMock('Bugsnag\Breadcrumbs', 'error_log');
        $log->expects($this->exactly(4))
            ->with($this->equalTo(
                'Bugsnag Warning: maxBreadcrumbs should be an integer between 0 and 100 (inclusive)'
            ));

        $recorder = new Recorder();

        $previousMax = $recorder->getMaxBreadcrumbs();

        $recorder->setMaxBreadcrumbs(10.1);
        $recorder->setMaxBreadcrumbs(new stdClass());
        $recorder->setMaxBreadcrumbs([1, 2, 3]);
        $recorder->setMaxBreadcrumbs(null);

        $this->assertSame($previousMax, $recorder->getMaxBreadcrumbs());
    }
}
