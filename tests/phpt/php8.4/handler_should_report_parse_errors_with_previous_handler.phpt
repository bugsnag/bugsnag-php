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
if (PHP_VERSION_ID < 80400) {
    echo 'SKIP — this test has different output on PHP <8.4';
}
?>
--EXPECTF--
object(ParseError)#%d (7) {
  ["message":protected]=>
  string(34) "syntax error, unexpected token "{""
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

Parse error: syntax error, unexpected token "{" in %s/parse_error.php on line 3
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - syntax error, unexpected token "{"
