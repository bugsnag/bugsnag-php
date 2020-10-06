--TEST--
Bugsnag\Handler should report uncaught exceptions
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

throw new Exception('abcxyz');

var_dump("I should never be reached!");
?>
--EXPECTF--
Fatal error: Uncaught Exception: abcxyz in %s:6
Stack trace:
#0 {main}
  thrown in %s on line 6
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - abcxyz
