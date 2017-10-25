<?php

require_once 'vendor/autoload.php';

$bugsnag = Bugsnag\Client::make(getenv('BUGSNAG_API_KEY'));

Bugsnag\Handler::register($bugsnag);

throw new Exception("Something went wrong");