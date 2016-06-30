<?php

class Bugsnag_ErrorTypes
{
    private static $ERROR_TYPES = [
        E_ERROR => [
            'name' => 'PHP Fatal Error',
            'severity' => 'error',
        ],

        E_WARNING => [
            'name' => 'PHP Warning',
            'severity' => 'warning',
        ],

        E_PARSE => [
            'name' => 'PHP Parse Error',
            'severity' => 'error',
        ],

        E_NOTICE => [
            'name' => 'PHP Notice',
            'severity' => 'info',
        ],

        E_CORE_ERROR => [
            'name' => 'PHP Core Error',
            'severity' => 'error',
        ],

        E_CORE_WARNING => [
            'name' => 'PHP Core Warning',
            'severity' => 'warning',
        ],

        E_COMPILE_ERROR => [
            'name' => 'PHP Compile Error',
            'severity' => 'error',
        ],

        E_COMPILE_WARNING => [
            'name' => 'PHP Compile Warning',
            'severity' => 'warning',
        ],

        E_USER_ERROR => [
            'name' => 'User Error',
            'severity' => 'error',
        ],

        E_USER_WARNING => [
            'name' => 'User Warning',
            'severity' => 'warning',
        ],

        E_USER_NOTICE => [
            'name' => 'User Notice',
            'severity' => 'info',
        ],

        E_STRICT => [
            'name' => 'PHP Strict',
            'severity' => 'info',
        ],

        E_RECOVERABLE_ERROR => [
            'name' => 'PHP Recoverable Error',
            'severity' => 'error',
        ],

        // E_DEPRECATED (Since PHP 5.3.0)
        8192 => [
            'name' => 'PHP Deprecated',
            'severity' => 'info',
        ],

        // E_USER_DEPRECATED (Since PHP 5.3.0)
        16384 => [
            'name' => 'User Deprecated',
            'severity' => 'info',
        ],
    ];

    /**
     * Is the given error code fatal?
     *
     * @param int $code the error code
     *
     * @return bool
     */
    public static function isFatal($code)
    {
        return self::getSeverity($code) == 'error';
    }

    /**
     * Get the name of the given error code.
     *
     * @param int $code the error code
     *
     * @return string
     */
    public static function getName($code)
    {
        if (array_key_exists($code, self::$ERROR_TYPES)) {
            return self::$ERROR_TYPES[$code]['name'];
        } else {
            return 'Unknown';
        }
    }

    /**
     * Get the severity of the given error code.
     *
     * @param int $code the error code
     *
     * @return string
     */
    public static function getSeverity($code)
    {
        if (array_key_exists($code, self::$ERROR_TYPES)) {
            return self::$ERROR_TYPES[$code]['severity'];
        } else {
            return 'error';
        }
    }

    /**
     * Get the the levels for the given severity.
     *
     * @param string $severity the given severity
     *
     * @return int
     */
    public static function getLevelsForSeverity($severity)
    {
        $levels = 0;
        foreach (self::$ERROR_TYPES as $level => $info) {
            if ($info['severity'] == $severity) {
                $levels |= $level;
            }
        }

        return $levels;
    }
}
