--TEST--
Bugsnag\Handler should report OOMs triggered by many small allocations
--FILE--
<?php

$client = require __DIR__ . '/../_prelude.php';

$handler = Bugsnag\Handler::register($client);

ini_set('memory_limit', '5M');

$size = 1024 * 256;

$array = new SplFixedArray($size);

for ($i = 0; $i < $size; ++$i) {
    $array[$i] = str_repeat('a', 32);
}

echo "No OOM!\n";
?>
--SKIPIF--
<?php
if (PHP_MAJOR_VERSION !== 5) {
    echo 'SKIP — this test does not run on PHP 7 & 8';
}
?>
--EXPECTF--
Fatal error: Allowed memory size of %d bytes exhausted (tried to allocate %d bytes) in %s on line 14
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Allowed memory size of %d bytes exhausted (tried to allocate %d bytes)
