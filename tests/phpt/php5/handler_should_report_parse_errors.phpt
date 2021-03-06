--TEST--
Bugsnag\Handler should report parse errors
--FILE--
<?php
$client = require __DIR__ . '/../_prelude.php';

Bugsnag\Handler::register($client);

include __DIR__ . '/../fixtures/parse_error.php';

var_dump('I should not be reached');
?>
--SKIPIF--
<?php
if (PHP_MAJOR_VERSION !== 5) {
    echo 'SKIP — this test has different output on PHP 7 & 8';
}
?>
--EXPECTF--
Parse error: syntax error, unexpected '{' in %s/parse_error.php on line 3
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - syntax error, unexpected '{'
