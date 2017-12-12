<?php

require_once 'vendor/autoload.php';

# Create the bugsnag client
$bugsnag = Bugsnag\Client::make();

# Notify of an exception, but manually attach some extra metadata to give more information
$bugsnag->notifyException(new RuntimeException("Oh no, something went wrong"), function($report) {
    $report->setMetaData([
        "account" => [
            "name" => "Acme Co.",
            "paying_customer" => true
        ],
        "diagnostics" => [
            "status" => 200,
            "error" => "nope"
        ]
    ]);
});