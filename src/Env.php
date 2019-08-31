<?php

namespace Bugsnag;

use Dotenv\Environment\DotenvFactory;
use Dotenv\Loader;

class Env
{
    /**
     * Get an environment variable.
     *
     * @param string $name
     *
     * @return string|null
     */
    public static function get($name)
    {
        return class_exists(DotenvFactory::class) ? self::getV3($name) : self::getV2($name);
    }

    /**
     * Get an environment variable using dotenv v3.
     *
     * @param string $name
     *
     * @return string|null
     */
    private static function getV3($name)
    {
        static $env;

        if ($env === null) {
            $env = (new DotenvFactory())->create();
        }

        return $env->get($name);
    }

    /**
     * Get an environment variable using dotenv v2.
     *
     * @param string $name
     *
     * @return string|null
     */
    private static function getV2($name)
    {
        static $loader;

        if ($loader === null) {
            $loader = new Loader('');
        }

        return $loader->getEnvironmentVariable($name);
    }
}
