<?php

require_once 'vendor/autoload.php';

# Create the bugsnag client
$bugsnag = Bugsnag\Client::make();

# Register the bugsnag error handlers 
Bugsnag\Handler::register($bugsnag);

# Register a callback that will be called whenever an error occurs
$bugsnag->registerCallback(function ($report) {
    $report->setMetaData([
        'account' => [
            'name' => 'Acme Co.',
            'paying_customer' => true
        ]
    ]);
});

# Throw an exception that will show up in your bugsnag dashboard
throw new RuntimeException("Something went wrong");