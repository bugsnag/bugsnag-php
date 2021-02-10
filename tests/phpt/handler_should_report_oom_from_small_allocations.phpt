--TEST--
Bugsnag\Handler should report OOMs triggered by many small allocations
--FILE--
<?php

$client = require __DIR__ . '/_prelude.php';

$handler = Bugsnag\Handler::register($client);

ini_set('memory_limit', '5M');

$i = 0;

gc_disable();

while ($i++ < 12345678) {
    $a = new stdClass;
    $a->b = $a;
}

echo "No OOM!\n";
?>
--SKIPIF--
<?php
if (PHP_MAJOR_VERSION < 7) {
    echo "SKIP - PHP 5 does not run OOM in this test";
}
?>
--EXPECTF--
Fatal error: Allowed memory size of %d bytes exhausted (tried to allocate %d bytes) in %s on line %d
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Allowed memory size of %d bytes exhausted (tried to allocate %d bytes)
