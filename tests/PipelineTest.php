<?php

namespace Bugsnag\Tests;

use Bugsnag\Pipeline;
use PHPUnit_Framework_TestCase as TestCase;

class TestCallbackA
{
    public function __invoke($item, $next)
    {
        $item .= 'A';
        $next($item);
    }
}

class TestCallbackB
{
    public function __invoke($item, $next)
    {
        $item .= 'B';
        $next($item);
    }
}

class TestCallbackC
{
    public function __invoke($item, $next)
    {
        $item .= 'C';
        $next($item);
    }
}

class PipelineTest extends TestCase
{
    public function testCallableAddedToPipeline()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $pipeline->execute('', function ($item) {
            $this->assertSame('A', $item);
        });
    }

    public function testCallableAddedInOrder()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $pipeline->pipe(new TestCallbackB());
        $pipeline->pipe(new TestCallbackC());
        $pipeline->execute('', function ($item) {
            $this->assertSame('ABC', $item);
        });
    }

    public function testInsertBeforeAddedCorrectly()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $pipeline->pipe(new TestCallbackC());
        $pipeline->insertBefore(new TestCallbackB(), 'Bugsnag\\Tests\\TestCallbackA');
        $pipeline->execute('', function ($item) {
            $this->assertSame('BAC', $item);
        });
    }

    public function testInsertBeforeNoClass()
    {
        $pipeline = new Pipeline();
        $pipeline->pipe(new TestCallbackA());
        $pipeline->pipe(new TestCallbackC());
        $pipeline->insertBefore(new TestCallbackB(), 'NotPresent');
        $pipeline->execute('', function ($item) {
            $this->assertSame('ACB', $item);
        });
    }
}
