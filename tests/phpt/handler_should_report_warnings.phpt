--TEST--
Bugsnag\Handler should report warnings
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

include __DIR__ . '/abc/xyz.php';

var_dump('Hello!');
?>
--EXPECTF--
Warning: include(%s/abc/xyz.php): failed to open stream: No such file or directory in %s on line 6

Warning: include(): Failed opening '%s/abc/xyz.php' for inclusion (include_path='%s') in %s on line 6
string(6) "Hello!"
Guzzle request made (2 events)!
* Method: 'POST'
* URI: 'http://localhost/notify'
