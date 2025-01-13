--TEST--
Bugsnag\Handler should report user errors

TODO this is currently reported twice! Once in the error handler and once on shutdown
--FILE--
<?php
$client = require __DIR__ . '/../_prelude.php';

Bugsnag\Handler::register($client);

throw new Error('abc');

var_dump('I should not be reached');
?>
--SKIPIF--
<?php
if(PHP_VERSION_ID < 80400) {
    echo 'SKIP — this case is already tested in PHP <8.4';
}
?>
--EXPECTF--
Fatal error: Uncaught Error: abc in %s:6
Stack trace:
#0 {main}
  thrown in %s on line 6
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - abc
