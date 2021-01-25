--TEST--
Bugsnag\Handler should report parse errors with a previous handler
--FILE--
<?php
$client = require __DIR__ . '/../_prelude.php';

set_exception_handler(function ($throwable) {
    var_dump($throwable);
    throw $throwable;
});

Bugsnag\Handler::register($client);

include __DIR__ . '/../fixtures/parse_error.php';

var_dump('I should not be reached');
?>
--SKIPIF--
<?php
if (PHP_MAJOR_VERSION !== 7) {
    echo 'SKIP — this test has different output on PHP 5 & 8';
}
?>
--EXPECTF--
object(ParseError)#%d (7) {
  ["message":protected]=>
  string(28) "syntax error, unexpected '{'"
  ["string":"Error":private]=>
  string(0) ""
  ["code":protected]=>
  int(0)
  ["file":protected]=>
  string(%d) "%s/parse_error.php"
  ["line":protected]=>
  int(3)
  ["trace":"Error":private]=>
  array(0) {
  }
  ["previous":"Error":private]=>
  NULL
}

Parse error: syntax error, unexpected '{' in %s/parse_error.php on line 3

Fatal error: Exception thrown without a stack frame in Unknown on line 0
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - syntax error, unexpected '{'
