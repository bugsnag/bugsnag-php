<?php

namespace Bugsnag\Tests;

use GrahamCampbell\TestBenchCore\MockeryTrait;
use GuzzleHttp\ClientInterface;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionObject;

abstract class TestCase extends BaseTestCase
{
    use PHPMock;
    use MockeryTrait;

    public function expectedException($class, $message = null)
    {
        if (class_exists(\PHPUnit_Framework_TestCase::class)) {
            $this->setExpectedException($class, $message);

            return;
        }

        $this->expectException($class);

        if ($message !== null) {
            $this->expectExceptionMessage($message);
        }
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

    private static function readObjectAttribute($object, $attributeName)
    {
        $reflector = new ReflectionObject($object);

        $attribute = $reflector->getProperty($attributeName);

        if (!$attribute || $attribute->isPublic()) {
            return $object->$attributeName;
        }

        $attribute->setAccessible(true);

        return $attribute->getValue($object);
    }
}
