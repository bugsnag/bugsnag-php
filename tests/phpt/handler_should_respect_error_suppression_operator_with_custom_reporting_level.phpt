--TEST--
Bugsnag\Handler should respect the error suppression operator with a custom reporting level
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';
$client->getConfig()->setErrorReportingLevel(E_ALL & ~E_USER_NOTICE);
error_reporting(E_ALL & ~E_USER_WARNING);

Bugsnag\Handler::register($client);

echo "Triggering a user notice that should be reported by PHP and ignored by Bugsnag\n";
trigger_error('abc notice', E_USER_NOTICE);

echo "Triggering a suppressed user notice that should be ignored by PHP and ignored by Bugsnag\n";
@trigger_error('xyz notice', E_USER_NOTICE);

echo "Triggering a user warning that should be ignored by PHP and reported by Bugsnag\n";
trigger_error('abc warning', E_USER_WARNING);

echo "Triggering a suppressed user warning that should be ignored by PHP and ignored by Bugsnag\n";
@trigger_error('xyz warning', E_USER_WARNING);
?>
--EXPECTF--
Triggering a user notice that should be reported by PHP and ignored by Bugsnag

Notice: abc notice in %s on line 9
Triggering a suppressed user notice that should be ignored by PHP and ignored by Bugsnag
Triggering a user warning that should be ignored by PHP and reported by Bugsnag
Triggering a suppressed user warning that should be ignored by PHP and ignored by Bugsnag
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - abc warning
