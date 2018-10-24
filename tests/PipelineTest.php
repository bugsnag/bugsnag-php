<?php

namespace Bugsnag\Tests;

use Bugsnag\Pipeline;
use PHPUnit_Framework_TestCase as TestCase;

class TestCallbackA
{
    public function __invoke($item, $next)
    {
        $payload = [
            'B' => array_key_exists('B', $item),
            'C' => array_key_exists('C', $item),
        ];
        $item['A'] = $payload;
        $next($item);
    }
}

class TestCallbackB
{
    public function __invoke($item, $next)
    {
        $payload = [
            'A' => array_key_exists('A', $item),
            'C' => array_key_exists('C', $item),
        ];
        $item['B'] = $payload;
        $next($item);
    }
}

class TestCallbackC
{
    public function __invoke($item, $next)
    {
        $payload = [
            'A' => array_key_exists('A', $item),
            'B' => array_key_exists('B', $item),
        ];
        $item['C'] = $payload;
        $next($item);
    }
}

class PipelineTest extends TestCase
{
    public function testCallableAddedToPipeline()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $pipeline->execute([], function ($item) {
            $this->assertCount(1, $item);
            $this->assertSame(['A' => ['B' => false, 'C' => false]], $item);
        });
    }

    public function testCallableAddedInOrder()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $pipeline->pipe(new TestCallbackB());
        $pipeline->pipe(new TestCallbackC());
        $pipeline->execute([], function ($item) {
            $this->assertCount(3, $item);
            $this->assertSame(['B' => false, 'C' => false], $item['A']);
            $this->assertSame(['A' => true, 'C' => false], $item['B']);
            $this->assertSame(['A' => true, 'B' => true], $item['C']);
        });
    }

    public function testInsertBeforeAddedCorrectly()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $pipeline->pipe(new TestCallbackC());
        $pipeline->insertBefore(new TestCallbackB(), 'TestCallbackA');
        $pipeline->execute([], function ($item) {
            $this->assertCount(3, $item);
            $this->assertSame(['B' => true, 'C' => false], $item['A']);
            $this->assertSame(['A' => false, 'C' => false], $item['B']);
            $this->assertSame(['A' => true, 'B' => true], $item['C']);
        });
    }

    public function testInsertBeforeNoClass()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $pipeline->pipe(new TestCallbackC());
        $pipeline->insertBefore(new TestCallbackB(), 'NotPresent');
        $pipeline->execute([], function ($item) {
            $this->assertCount(3, $item);
            $this->assertSame(['B' => false, 'C' => false], $item['A']);
            $this->assertSame(['A' => true, 'C' => true], $item['B']);
            $this->assertSame(['A' => true, 'B' => false], $item['C']);
        });
    }
}
