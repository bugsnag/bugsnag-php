<?php

require_once 'vendor/autoload.php';

$bugsnag = Bugsnag\Client::make('YOUR-API-KEY-HERE');
$bugsnag->notifyError('Broken', 'Something broke', function (Bugsnag\Report $report) {
    $report->setMetaData(['tab' => ['paying' => true, 'object' => (object) ['key' => 'value'], 'null' => null, 'string' => 'test', 'int' => 4]]);
});
