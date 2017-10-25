<?php

require_once 'vendor/autoload.php';

$bugsnag = Bugsnag\Client::make(getenv('BUGSNAG_API_KEY'));

$bugsnag->notifyError('Broken', 'Something broke', function (Bugsnag\Report $report) {
    $report->setMetaData(['tab' => ['paying' => true, 'object' => (object) ['key' => 'value'], 'null' => null, 'string' => 'test', 'int' => 4]]);
});