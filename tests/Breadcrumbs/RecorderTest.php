<?php

namespace Bugsnag\Tests\Breadcrumbs;

use Bugsnag\Breadcrumbs\Breadcrumb;
use Bugsnag\Breadcrumbs\Recorder;
use Iterator;
use PHPUnit_Framework_TestCase as TestCase;

class RecorderTest extends TestCase
{
    public function testIterable()
    {
        $recorder = new Recorder();

        $this->assertInstanceOf(Iterator::class, $recorder);

        $this->assertSame([], iterator_to_array($recorder));
    }

    public function testOneRecorded()
    {
        $recorder = new Recorder();
        $breadcrumb = new Breadcrumb('Foo', 'error');

        $recorder->record($breadcrumb);

        $this->assertSame([$breadcrumb], iterator_to_array($recorder));
    }

    public function testTwoRecorded()
    {
        $recorder = new Recorder();
        $one = new Breadcrumb('Foo', 'error');
        $two = new Breadcrumb('Bar', 'user');

        $recorder->record($one);
        $recorder->record($two);

        $this->assertSame([$one, $two], iterator_to_array($recorder));
    }

    public function testManyRecorded()
    {
        $recorder = new Recorder();
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

        $this->assertSame([$two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $two, $three, $two, $three], iterator_to_array($recorder));
    }
}
