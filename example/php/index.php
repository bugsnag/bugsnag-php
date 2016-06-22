<?php

require_once 'vendor/autoload.php';

$config = new Bugsnag\Configuration('YOUR-API-KEY-HERE');
$bugsnag = new Bugsnag\Client($config);
$bugsnag->notifyError('Broken', 'Something broke', ['tab' => ['paying' => true, 'object' => (object) ['key' => 'value'], 'null' => null, 'string' => 'test', 'int' => 4]]);
