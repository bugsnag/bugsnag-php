--TEST--
Bugsnag\Handler should handle all throwables being reraised
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

set_exception_handler(function ($throwable) {
    var_dump($throwable);
    throw $throwable;
});

Bugsnag\Handler::register($client);

throw new DivisionByZeroError('22 / 0 = ???');

var_dump('I should not be reached');
?>
--SKIPIF--
<?php
if (PHP_MAJOR_VERSION < 7) {
    echo 'SKIP - the Error type does not exist until PHP 7';
}
?>
--EXPECTF--
object(DivisionByZeroError)#%d (7) {
  ["message":protected]=>
  string(12) "22 / 0 = ???"
  ["string":"Error":private]=>
  string(0) ""
  ["code":protected]=>
  int(0)
  ["file":protected]=>
  string(%d) "%s"
  ["line":protected]=>
  int(11)
  ["trace":"Error":private]=>
  array(0) {
  }
  ["previous":"Error":private]=>
  NULL
}

Fatal error: Uncaught DivisionByZeroError: 22 / 0 = ??? in %s:11
Stack trace:
#0 {main}
  thrown in %s on line 11
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - 22 / 0 = ???
