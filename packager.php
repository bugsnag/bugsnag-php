<?php

require __DIR__.'/vendor/autoload.php';

$packager = new Burgomaster(__DIR__.'/build/staging', __DIR__);

$packager->deepCopy('LICENSE.txt', 'LICENSE.txt');

$packager->recursiveCopy('src', 'Bugsnag', ['php']);

$packager->createPhar(__DIR__.'/build/bugsnag.phar');
