--TEST--
Bugsnag\Handler should not increase the memory limit when memoryLimitIncrease is disabled
--FILE--
<?php
$client = require __DIR__ . '/../_prelude.php';
$client->setMemoryLimitIncrease(null);

ini_set('memory_limit', '5M');
var_dump(ini_get('memory_limit'));

$client->registerCallback(function () {
    // This should be the same as the first var_dump, because we should not have
    // increase the memory limit
    var_dump(ini_get('memory_limit'));
});

Bugsnag\Handler::register($client);

$a = str_repeat('a', 2147483647);

echo "No OOM!\n";
?>
--SKIPIF--
<?php
if (PHP_VERSION_ID < 80500) {
    echo 'SKIP — this case is already tested in PHP <8.5';
}
?>
--EXPECTF--
string(2) "5M"

Fatal error: Allowed memory size of %d bytes exhausted (tried to allocate %d bytes) in %s on line 16
Stack trace:
#0 Standard input code(16): str_repeat('a', 2147483647)
#1 {main}
string(2) "5M"
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Allowed memory size of %d bytes exhausted (tried to allocate %d bytes)
