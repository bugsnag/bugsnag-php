--TEST--
Bugsnag\Handler should use the previous error handler's return value
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

$hideError = true;

set_error_handler(function ()use (&$hideError) {
    return $hideError;
});

Bugsnag\Handler::registerWithPrevious($client);

var_dump('Triggering notice with hide error:', $hideError);
new stdClass == 1;

$hideError = false;
var_dump('Triggering notice with hide error:', $hideError);
new stdClass == 1;
?>
--EXPECTF--
string(34) "Triggering notice with hide error:"
bool(true)
string(34) "Triggering notice with hide error:"
bool(false)

Notice: Object of class stdClass could not be converted to int in %s on line 17
Guzzle request made (2 events)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Object of class stdClass could not be converted to int
    - Object of class stdClass could not be converted to int
