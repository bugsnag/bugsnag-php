<?php

require_once 'vendor/autoload.php';

$bugsnag = Bugsnag\Client::make(getenv('BUGSNAG_API_KEY'));

Bugsnag\Handler::register($bugsnag);

$bugsnag->registerCallback(function ($report) {
    $report->setMetaData([
        'account' => [
            'name' => 'Test'
        ]
    ]);
});

throw new Exception("Something went wrong");