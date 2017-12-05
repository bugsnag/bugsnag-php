<?php

namespace Bugsnag\Tests\Breadcrumbs;

use Bugsnag\Breadcrumbs\Breadcrumb;
use PHPUnit_Framework_TestCase as TestCase;

class BreadcrumbTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The breadcrumb name must be a string.
     */
    public function testBadName()
    {
        new Breadcrumb(123, 'error');
    }

    public function testEmptyName()
    {
        $breadcrumb = new Breadcrumb('', 'error');
        $this->assertSame('', $breadcrumb->toArray()['name']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The breadcrumb name must be at most 30 characters in length.
     */
    public function testLongName()
    {
        new Breadcrumb('This error name is far too long to be allowed through.', 'error');
    }

    public function testGoodName()
    {
        $breadcrumb = new Breadcrumb('Good name!', 'error');

        $this->assertSame('Good name!', $breadcrumb->toArray()['name']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The breadcrumb type must be one of the set of 8 standard types.
     */
    public function testBadType()
    {
        new Breadcrumb('Foo', 'bar');
    }

    public function testGoodType()
    {
        $breadcrumb = new Breadcrumb('Foo', 'request');

        $this->assertSame('request', $breadcrumb->toArray()['type']);
    }

    public function testMetaData()
    {
        $breadcrumb = new Breadcrumb('Foo', 'request', ['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $breadcrumb->getMetaData());
    }

    public function testToArray()
    {
        $breadcrumb = new Breadcrumb('Foo', 'request', ['foo' => 'bar']);

        $this->assertInternalType('array', $breadcrumb->toArray());
        $this->assertCount(3, $breadcrumb->toArray());
        $this->assertInternalType('string', $breadcrumb->toArray()['timestamp']);
        $this->assertSame('Foo', $breadcrumb->toArray()['name']);
        $this->assertSame('request', $breadcrumb->toArray()['type']);
    }

    public function testGetTypes()
    {
        $this->assertSame(['navigation', 'request', 'process', 'log', 'user', 'state', 'error', 'manual'], Breadcrumb::getTypes());
    }
}
