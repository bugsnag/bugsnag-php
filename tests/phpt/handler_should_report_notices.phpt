--TEST--
Bugsnag\Handler should report notices
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

new stdClass == 1;

var_dump('Hello!');
?>
--EXPECTF--
Notice: Object of class stdClass could not be converted to int in %s on line 6
string(6) "Hello!"
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Object of class stdClass could not be converted to int
