--TEST--
Bugsnag\Handler should report OOMs triggered by single large allocations
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

$a = str_repeat('a', PHP_INT_MAX);

echo "No OOM!\n";
?>
--EXPECTF--
Fatal error: Allowed memory size of %d bytes exhausted (tried to allocate %d bytes) in %s on line 6
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Allowed memory size of %d bytes exhausted (tried to allocate %d bytes)
