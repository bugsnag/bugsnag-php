--TEST--
Bugsnag\Handler should increase the memory limit by the configured amount when an OOM happens
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';
$client->setMemoryLimitIncrease(1024 * 1024 * 10);

ini_set('memory_limit', '2M');
var_dump(ini_get('memory_limit'));

$client->registerCallback(function () {
    var_dump(ini_get('memory_limit'));
});

Bugsnag\Handler::register($client);

$a = str_repeat('a', 2147483647);

echo "No OOM!\n";
?>
--EXPECTF--
string(2) "2M"

Fatal error: Allowed memory size of %d bytes exhausted (tried to allocate %d bytes) in %s on line 14
string(8) "12582912"
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Allowed memory size of %d bytes exhausted (tried to allocate %d bytes)
