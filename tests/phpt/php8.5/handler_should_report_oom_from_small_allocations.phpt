--TEST--
Bugsnag\Handler should report OOMs triggered by many small allocations
--FILE--
<?php

$client = require __DIR__ . '/../_prelude.php';

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
if (PHP_VERSION_ID < 80500) {
    echo 'SKIP — this case is already tested in PHP <8.5';
}
?>
--EXPECTF--
Fatal error: Allowed memory size of %d bytes exhausted (tried to allocate %d bytes) in %s on line %d
Stack trace:
#0 {main}
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Allowed memory size of %d bytes exhausted (tried to allocate %d bytes)
