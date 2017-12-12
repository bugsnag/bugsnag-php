<?php

require_once 'vendor/autoload.php';

# Create the bugsnag client
$bugsnag = Bugsnag\Client::make();

# Register the bugsnag error handlers 
Bugsnag\Handler::register($bugsnag);

# Throw an exception that will show up in your bugsnag dashboard
throw new RuntimeException("Something went wrong");