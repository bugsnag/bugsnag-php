<?php

require_once 'vendor/autoload.php';

// Create the bugsnag client
$bugsnag = Bugsnag\Client::make();

// Call the notifyException function to manually notify bugsnag
// You can also notify with an error by calling notifyError
$bugsnag->notifyException(new RuntimeException('Oh no, something went wrong'));
