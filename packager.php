<?php

date_default_timezone_set('UTC');

require __DIR__.'/vendor/autoload.php';

$packager = new Burgomaster(__DIR__.'/build/staging', __DIR__);

$packager->deepCopy('LICENSE.txt', 'LICENSE.txt');

$packager->recursiveCopy('src', 'Bugsnag', ['php']);

$packager->createAutoloader();

$packager->createPhar(__DIR__.'/build/bugsnag.phar');
