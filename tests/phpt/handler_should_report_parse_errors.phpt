--TEST--
Bugsnag\Handler should report parse errors

TODO this should also run in PHP 7!
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

include __DIR__ . '/fixtures/parse_error.php';

var_dump('I should not be reached');
?>
--SKIPIF--
<?php
if (PHP_MAJOR_VERSION > 5) {
    echo 'SKIP - PHP 7 does not output the parse error';
}
?>
--EXPECTF--
Parse error: syntax error, unexpected '{' in %s/parse_error.php on line 3
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - syntax error, unexpected '{'
