<?php

namespace Bugsnag;

use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client as Guzzle;

class Utils
{
    /**
     * Checks whether the given function name is available.
     *
     * @param string $func the function name
     *
     * @return bool
     */
    public static function functionAvailable($func)
    {
        $disabled = explode(',', ini_get('disable_functions'));

        return function_exists($func) && !in_array($func, $disabled);
    }

    /**
     * Gets the current user's identity for build reporting.
     *
     * @return string
     */
    public static function getBuilderName()
    {
        $builderName = null;
        if (self::functionAvailable('exec')) {
            $output = [];
            $success = 0;
            exec('whoami', $output, $success);
            if ($success == 0) {
                $builderName = $output[0];
            }
        }
        if (is_null($builderName)) {
            $builderName = get_current_user();
        }

        return $builderName;
    }

    /**
     * Make a new guzzle client instance.
     *
     * @param array       $options
     *
     * @return \GuzzleHttp\ClientInterface
     */
    public static function makeGuzzle(array $options = [])
    {
        if ($path = static::getCaBundlePath()) {
            $options['verify'] = $path;
        }

        return new Guzzle($options);
    }

    /**
     * Get the ca bundle path if one exists.
     *
     * @return string|false
     */
    protected static function getCaBundlePath()
    {
        if (!class_exists(CaBundle::class)) {
            return false;
        }

        return realpath(CaBundle::getSystemCaRootBundlePath());
    }
}
