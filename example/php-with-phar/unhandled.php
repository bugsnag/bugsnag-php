<?php

// Includes Bugsnag from a downloaded php archive file.  These should be located
// in the same folder as this script.  See the Readme for more information.
include "phar://bugsnag.phar";
include "phar://guzzle.phar";

// Create the bugsnag client
$bugsnag = Bugsnag\Client::make();

// Register the bugsnag error handlers
Bugsnag\Handler::register($bugsnag);

// Throw an exception that will show up in your bugsnag dashboard
throw new RuntimeException('Something went wrong');