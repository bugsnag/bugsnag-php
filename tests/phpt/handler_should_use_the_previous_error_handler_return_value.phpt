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
$a = $b;

$hideError = false;
var_dump('Triggering notice with hide error:', $hideError);
$a = $b;
?>
--EXPECTF--
string(34) "Triggering notice with hide error:"
bool(true)
string(34) "Triggering notice with hide error:"
bool(false)

Notice: Undefined variable: b in %s on line 17
Guzzle request made (2 events)!
* Method: 'POST'
* URI: 'http://localhost/notify'
