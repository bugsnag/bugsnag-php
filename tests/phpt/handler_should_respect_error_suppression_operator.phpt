--TEST--
Bugsnag\Handler should respect the error suppression operator level
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

@$a = $b; // should not be reported
$c = $d;

?>
--EXPECTF--
Notice: Undefined variable: d in %s on line 7
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
