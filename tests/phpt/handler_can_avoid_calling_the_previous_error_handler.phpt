--TEST--
Bugsnag\Handler can avoid calling the previous error handler
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

set_error_handler(function () {
    var_dump(func_get_args());
    return false;
});

$handler = new Bugsnag\Handler($client);
$handler->registerErrorHandler(false);

new stdClass() == 1;

var_dump('Hello!');

include __DIR__ . '/abc/xyz.php';
?>
--EXPECTF--
Notice: Object of class stdClass could not be converted to int in %s on line 12
string(6) "Hello!"

Warning: include(%s/abc/xyz.php): %cailed to open stream: No such file or directory in %s on line 16

Warning: include(): Failed opening '%s/abc/xyz.php' for inclusion (include_path='%s') in %s on line 16
Guzzle request made (3 events)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Object of class stdClass could not be converted to int
    - include(%s/abc/xyz.php): %cailed to open stream: No such file or directory
    - include(): Failed opening '%s/abc/xyz.php' for inclusion (include_path='%s')
