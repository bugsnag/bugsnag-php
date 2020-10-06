--TEST--
Bugsnag\Handler should report compile errors

https://github.com/php/php-src/blob/2772751b58ee579a8f1288a0949e5e1fcb554877/Zend/zend_API.c#L3965-L3968
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

include __DIR__ . '/fixtures/compile_error.php';

var_dump('I should not be reached');
?>
--SKIPIF--
<?php
if (PHP_MAJOR_VERSION < 7) {
    echo 'SKIP — this is a different error in PHP 5';
}
?>
--EXPECTF--
Fatal error: A class constant must not be called 'class'; it is reserved for class name fetching in %s/compile_error.php on line 9
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - A class constant must not be called 'class'; it is reserved for class name fetching
