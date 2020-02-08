<?php

namespace Bugsnag\Tests;

use GrahamCampbell\TestBenchCore\MockeryTrait;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use PHPMock;
    use MockeryTrait;
    public function expectedException($class, $msg = null)
    {
        if (class_exists(\PHPUnit_Framework_TestCase::class)) {
            $this->setExpectedException($class, $msg);
        } else {
            $this->expectException($class);
            if ($msg !== null) {
                $this->expectExceptionMessage($msg);
            }
        }
    }
}
