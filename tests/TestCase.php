<?php

namespace Bugsnag\Tests;

use GrahamCampbell\TestBenchCore\MockeryTrait;
use GuzzleHttp\ClientInterface;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\Exception as ExceptionStub;
use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnit\Runner\Version as PhpUnitVersion;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    use PHPMock;
    use MockeryTrait;

    public function expectedException($class, $message = null)
    {
        if ($this->isPhpUnit4()) {
            $this->setExpectedException($class, $message);

            return;
        }

        $this->expectException($class);

        if ($message !== null) {
            $this->expectExceptionMessage($message);
        }
    }

    /**
     * Wrapper around 'willThrowException' with support for Throwable on PHPUnit
     * versions before 7.
     *
     * @param MockObject   $mock
     * @param InvokedCount $invokedCount
     * @param string       $methodName
     * @param Throwable    $throwable
     *
     * @return void
     */
    protected function willThrow(
        MockObject $mock,
        InvokedCount $invokedCount,
        $methodName,
        Throwable $throwable
    ) {
        // Before PHPUnit 7 'willThrowException' required an Exception, rather
        // than a Throwable so we have to handle these versions differently
        if ($this->isPhpUnit7()) {
            $mock->expects($invokedCount)
                ->method($methodName)
                ->withAnyParameters()
                ->willThrowException($throwable);
        } else {
            $mock->expects($invokedCount)
                ->method($methodName)
                ->withAnyParameters()
                ->will(new ExceptionStub($throwable));
        }
    }

    protected function isPhpUnit7()
    {
        return version_compare($this->phpUnitVersion(), '7.0.0', '>=')
            && version_compare($this->phpUnitVersion(), '8.0.0', '<');
    }

    protected function isPhpUnit6()
    {
        return version_compare($this->phpUnitVersion(), '6.0.0', '>=')
            && version_compare($this->phpUnitVersion(), '7.0.0', '<');
    }

    protected function isPhpUnit4()
    {
        return version_compare($this->phpUnitVersion(), '4.0.0', '>=')
            && version_compare($this->phpUnitVersion(), '5.0.0', '<');
    }

    /**
     * @return string
     */
    protected static function getGuzzleMethod()
    {
        return method_exists(ClientInterface::class, 'request') ? 'request' : 'post';
    }

    private function phpUnitVersion()
    {
        // Support versions of PHPUnit before 6.0.0 when native namespaces were
        // introduced for the Version class
        if (class_exists(\PHPUnit_Runner_Version::class)) {
            return \PHPUnit_Runner_Version::id();
        }

        return PhpUnitVersion::id();
    }
}
