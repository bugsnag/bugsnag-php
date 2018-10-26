<?php

namespace Bugsnag;

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
     * Check a string ends in a substring.
     *
     * @param string $haystack The parent string
     * @param string $sneedle The substring
     *
     * @return bool
     */
    public static function stringEndsIn($haystack, $needle)
    {
        return substr($haystack, -strlen($needle)) === $needle;
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
}
