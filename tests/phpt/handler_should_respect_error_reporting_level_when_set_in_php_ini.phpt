--TEST--
Bugsnag\Handler should respect the error_reporting level when set in php.ini
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

Bugsnag\Handler::register($client);

ini_set('error_reporting', E_ALL & ~E_USER_NOTICE);

trigger_error('hello E_USER_NOTICE', E_USER_NOTICE); // should not be reported
trigger_error('hello E_USER_DEPRECATED', E_USER_DEPRECATED);

ini_set('error_reporting', E_ALL);

trigger_error('hello E_USER_NOTICE 2', E_USER_NOTICE);
trigger_error('hello E_USER_DEPRECATED 2', E_USER_DEPRECATED);

?>
--EXPECTF--
Deprecated: hello E_USER_DEPRECATED in %s on line 9

Notice: hello E_USER_NOTICE 2 in %s on line 13

Deprecated: hello E_USER_DEPRECATED 2 in %s on line 14
Guzzle request made (3 events)!
* Method: 'POST'
* URI: 'http://localhost/notify'
