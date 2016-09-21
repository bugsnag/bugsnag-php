<?php

// Loaded Bugsnag here
$bugsnag = require 'runtime.php';

$bugsnag->leaveBreadcrumb('Example breadcrumb!');

function sendBugsnagError()
{
    global $bugsnag;

    $bugsnag->notifyError('Broken', 'Something broke', function (Bugsnag\Report $report) {
        $report->setMetaData(['tab' => [
            'paying' => true,
            'object' => (object) ['key' => 'value'],
            'null' => null,
            'string' => 'test',
            'int' => 4,
        ]]);
    });
}

sendBugsnagError();
