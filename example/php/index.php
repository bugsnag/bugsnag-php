<?php

require_once 'vendor/autoload.php';

$bugsnag = new Bugsnag\Client::make('YOUR-API-KEY-HERE');
$bugsnag->notifyError('Broken', 'Something broke', ['tab' => ['paying' => true, 'object' => (object) ['key' => 'value'], 'null' => null, 'string' => 'test', 'int' => 4]]);
