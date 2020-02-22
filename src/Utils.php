<?php

namespace Bugsnag;

use Symfony\Component\Process\Process;

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
        return self::getBuilderUsingSymfonyProcess() ?: self::getBuilderUsingNativeExec() ?: get_current_user();
    }

    private static function getBuilderUsingSymfonyProcess()
    {
        if (!class_exists(Process::class)) {
            return;
        }

        $process = new Process('whoami');
        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }
    }

    private static function getBuilderUsingNativeExec()
    {
        if (!self::functionAvailable('exec')) {
            return;
        }

        $output = [];
        $success = 0;

        exec('whoami', $output, $success);

        if ($success == 0) {
            return $output[0];
        }
    }
}
