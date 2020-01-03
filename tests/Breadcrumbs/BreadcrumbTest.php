<?php

namespace Bugsnag\Tests\Breadcrumbs;

use Bugsnag\Breadcrumbs\Breadcrumb;
use Bugsnag\Tests\TestCase;

class BreadcrumbTest extends TestCase
{
    public function testBadName()
    {
        $breadcrumb = new Breadcrumb(123, 'error');
        $this->assertSame('<no name>', $breadcrumb->toArray()['name']);
        $this->assertSame('Breadcrumb name must be a string - integer provided instead', $breadcrumb->getMetaData()['BreadcrumbError']);
    }

    public function testEmptyName()
    {
        $breadcrumb = new Breadcrumb('', 'error');
        $this->assertSame('<no name>', $breadcrumb->toArray()['name']);
        $this->assertSame('Empty string provided as the breadcrumb name', $breadcrumb->getMetaData()['BreadcrumbError']);
    }

    public function testNullName()
    {
        $breadcrumb = new Breadcrumb(null, 'error');
        $this->assertSame('<no name>', $breadcrumb->toArray()['name']);
        $this->assertSame('NULL provided as the breadcrumb name', $breadcrumb->getMetaData()['BreadcrumbError']);
    }

    public function testGoodName()
    {
        $breadcrumb = new Breadcrumb('Good name!', 'error');

        $this->assertSame('Good name!', $breadcrumb->toArray()['name']);
    }

    public function testBadType()
    {
        $this->expectedException(
            \InvalidArgumentException::class,
            'The breadcrumb type must be one of the set of 8 standard types.'
        );

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
