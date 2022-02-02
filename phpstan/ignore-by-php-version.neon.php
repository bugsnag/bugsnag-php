<?php

declare(strict_types=1);

/**
 * Ignore errors based on the current PHP version.
 *
 * This is useful in cases where errors are caused by the use of features that
 * do not exist in the current version of PHP, e.g. enums
 *
 * This is the approach used by PHPStan itself:
 * https://github.com/phpstan/phpstan-src/blob/master/build/ignore-by-php-version.neon.php
 */

use PHPStan\DependencyInjection\NeonAdapter;

$config = [];
$adapter = new NeonAdapter();

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    $config = array_merge_recursive($config, $adapter->load(__DIR__.'/baseline-less-than-8.1.neon'));
}

$config['parameters']['phpVersion'] = PHP_VERSION_ID;

return $config;
