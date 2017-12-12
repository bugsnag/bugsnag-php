<?php

require_once 'vendor/autoload.php';

# Create the bugsnag client
$bugsnag = Bugsnag\Client::make();

# Notify of the exception but modify the severity in a callback to adjust the severity reported on the dashboard
$bugsnag->notifyException(new RuntimeException("Oh no, something went wrong"), function($report) {
    $report->setSeverity('info');
});