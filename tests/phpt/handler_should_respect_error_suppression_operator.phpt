--TEST--
Bugsnag\Handler should respect the error suppression operator
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

@(new stdClass == 1); // should not be reported
new stdClass == 1;

?>
--EXPECTF--
Notice: Object of class stdClass could not be converted to int in %s on line 7
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Object of class stdClass could not be converted to int
