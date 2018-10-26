<?php

namespace Bugsnag\Tests;

use Bugsnag\Pipeline;
use PHPUnit_Framework_TestCase as TestCase;

class ReturnObject
{
    public $result = '';
}

class TestCallbackA
{
    public function __invoke($item, $next)
    {
        $item->result .= 'A';
        $next($item);
    }
}

class TestCallbackB
{
    public function __invoke($item, $next)
    {
        $item->result .= 'B';
        $next($item);
    }
}

class TestCallbackC
{
    public function __invoke($item, $next)
    {
        $item->result .= 'C';
        $next($item);
    }
}

class PipelineTest extends TestCase
{
    public function testCallableAddedToPipeline()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $returnItem = new ReturnObject();
        $pipeline->execute($returnItem, function ($item) {});
        $this->assertSame('A', $returnItem->result);
    }

    public function testCallableAddedInOrder()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $pipeline->pipe(new TestCallbackB());
        $pipeline->pipe(new TestCallbackC());
        $returnItem = new ReturnObject();
        $pipeline->execute($returnItem, function ($item) {});
        $this->assertSame('ABC', $returnItem->result);
    }

    public function testInsertBeforeAddedCorrectly()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $pipeline->pipe(new TestCallbackC());
        $pipeline->insertBefore(new TestCallbackB(), 'Bugsnag\\Tests\\TestCallbackA');
        $returnItem = new ReturnObject();
        $pipeline->execute($returnItem, function ($item) {});
        $this->assertSame('BAC', $returnItem->result);
    }

    public function testInsertBeforeNoClass()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $pipeline->pipe(new TestCallbackC());
        $pipeline->insertBefore(new TestCallbackB(), 'NotPresent');
        $returnItem = new ReturnObject();
        $pipeline->execute($returnItem, function ($item) {});
        $this->assertSame('ACB', $returnItem->result);
    }
}
