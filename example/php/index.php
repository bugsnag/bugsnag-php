<?php

require_once '../../build/bugsnag.phar';

$bugsnag = new Bugsnag_Client('YOUR-API-KEY-HERE');
$bugsnag->notifyError('Broken', 'Something broke', ['tab' => ['paying' => true, 'object' => (object) ['key' => 'value'], 'null' => null, 'string' => 'test', 'int' => 4]]);
