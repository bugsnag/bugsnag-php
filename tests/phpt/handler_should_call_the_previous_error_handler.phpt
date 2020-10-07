--TEST--
Bugsnag\Handler should call the previous error handler
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

set_error_handler(function () {
    var_dump(func_get_args());
    return false;
});

Bugsnag\Handler::registerWithPrevious($client);

new stdClass == 1;

var_dump('Hello!');

include __DIR__ . '/abc/xyz.php';
?>
--EXPECTF--
array(4) {
  [0]=>
  int(8)
  [1]=>
  string(54) "Object of class stdClass could not be converted to int"
  [2]=>
  string(%d) "%s"
  [3]=>
  int(11)
}

Notice: Object of class stdClass could not be converted to int in %s on line 11
string(6) "Hello!"
array(4) {
  [0]=>
  int(2)
  [1]=>
  string(%d) "include(%s/abc/xyz.php): %cailed to open stream: No such file or directory"
  [2]=>
  string(%d) "%s"
  [3]=>
  int(15)
}

Warning: include(%s/abc/xyz.php): %cailed to open stream: No such file or directory in %s on line 15
array(4) {
  [0]=>
  int(2)
  [1]=>
  string(%d) "include(): Failed opening '%s/abc/xyz.php' for inclusion (include_path='%s')"
  [2]=>
  string(%d) "%s"
  [3]=>
  int(15)
}

Warning: include(): Failed opening '%s/abc/xyz.php' for inclusion (include_path='%s') in %s on line 15
Guzzle request made (3 events)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Object of class stdClass could not be converted to int
    - include(%s/abc/xyz.php): %cailed to open stream: No such file or directory
    - include(): Failed opening '%s/abc/xyz.php' for inclusion (include_path='%s')
