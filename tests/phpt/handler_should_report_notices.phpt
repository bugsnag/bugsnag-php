--TEST--
Bugsnag\Handler should report notices
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

$a = $b;

var_dump('Hello!');
?>
--EXPECTF--
Notice: Undefined variable: b in %s on line 6
string(6) "Hello!"
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
