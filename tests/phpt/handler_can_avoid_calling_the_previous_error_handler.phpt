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

$a = $b;

var_dump('Hello!');

include __DIR__ . '/abc/xyz.php';
?>
--EXPECTF--
Notice: Undefined variable: b in %s on line 12
string(6) "Hello!"

Warning: include(%s/abc/xyz.php): failed to open stream: No such file or directory in %s on line 16

Warning: include(): Failed opening '%s/abc/xyz.php' for inclusion (include_path='%s') in %s on line 16
Guzzle request made (3 events)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Undefined variable: b
    - include(%s/abc/xyz.php): failed to open stream: No such file or directory
    - include(): Failed opening '%s/abc/xyz.php' for inclusion (include_path='%s')
