--TEST--
Bugsnag\Handler should report user notices
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

trigger_error('abc', E_USER_NOTICE);

var_dump('Hello!');
?>
--EXPECTF--
Notice: abc in %s on line 6
string(6) "Hello!"
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - abc
