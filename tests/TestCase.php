<?php

namespace Bugsnag\Tests;

use GrahamCampbell\TestBenchCore\MockeryTrait;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Uri;
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
     * @param \GuzzleHttp\Client $guzzle
     *
     * @return GuzzleHttp\Psr7\Uri|null
     */
    protected static function getGuzzleBaseUri(Guzzle $guzzle)
    {
        if (method_exists($guzzle, 'getBaseUrl')) {
            return new Uri($guzzle->getBaseUrl());
        }

        $config = self::readObjectAttribute($guzzle, 'config');

        return isset($config['base_uri']) ? $config['base_uri'] : null;
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
