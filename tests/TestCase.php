<?php

namespace Bugsnag\Tests;

use GuzzleHttp\ClientInterface;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnit\Runner\Version as PhpUnitVersion;

abstract class TestCase extends BaseTestCase
{
    use PHPMock;

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

    protected function isPhpUnit7()
    {
        return version_compare($this->phpUnitVersion(), '7.0.0', '>=')
            && version_compare($this->phpUnitVersion(), '8.0.0', '<');
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

    /**
     * @return string
     */
    protected function getGuzzleBaseOptionName()
    {
        return $this->isUsingGuzzle5() ? 'base_url' : 'base_uri';
    }

    /**
     * @return bool
     */
    protected function isUsingGuzzle5()
    {
        return method_exists(ClientInterface::class, 'getBaseUrl');
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
