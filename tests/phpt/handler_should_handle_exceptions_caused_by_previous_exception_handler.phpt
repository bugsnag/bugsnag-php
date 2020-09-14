--TEST--
Bugsnag\Handler should handle exceptions caused by the previous exception handler
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

set_exception_handler(function ($throwable) {
    var_dump($throwable);
    throw new BadMethodCallException('oh dear');
});

Bugsnag\Handler::registerWithPrevious($client);

throw new RuntimeException('abc xyz');

var_dump('I should not be reached');
?>
--EXPECTF--
object(RuntimeException)#15 (7) {
  ["message":protected]=>
  string(7) "abc xyz"
  ["string":"Exception":private]=>
  string(0) ""
  ["code":protected]=>
  int(0)
  ["file":protected]=>
  string(%d) "%s"
  ["line":protected]=>
  int(11)
  ["trace":"Exception":private]=>
  array(0) {
  }
  ["previous":"Exception":private]=>
  NULL
}
Guzzle request made (2 events)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - abc xyz
    - oh dear
