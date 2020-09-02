--TEST--
Bugsnag\Handler should report user errors

TODO this is currently reported twice! Once in the error handler and once on shutdown
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

trigger_error('abc', E_USER_ERROR);

var_dump('I should not be reached');
?>
--EXPECTF--
Fatal error: abc in %s on line 6
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
