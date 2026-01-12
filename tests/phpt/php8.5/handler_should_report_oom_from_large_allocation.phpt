--TEST--
Bugsnag\Handler should report OOMs triggered by single large allocations
--FILE--
<?php
$client = require __DIR__ . '/../_prelude.php';

ini_set('memory_limit', '5M');

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
Fatal error: Allowed memory size of %d bytes exhausted (tried to allocate %d bytes) in %s on line 8
Stack trace:
#0 Standard input code(8): str_repeat('a', 2147483647)
#1 {main}
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Allowed memory size of %d bytes exhausted (tried to allocate %d bytes)
