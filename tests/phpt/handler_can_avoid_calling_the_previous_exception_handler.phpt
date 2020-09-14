--TEST--
Bugsnag\Handler can avoid calling the previous exception handler
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

set_exception_handler(function ($throwable) {
    var_dump($throwable);
});

$handler = new Bugsnag\Handler($client);
$handler->registerExceptionHandler(false);

throw new RuntimeException('abc xyz');

var_dump('I should not be reached');
?>
--EXPECTF--
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - abc xyz
