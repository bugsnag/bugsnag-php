--TEST--
Bugsnag\Configuration should warn if error reporting level is set incorrectly
--FILE--
<?php
// PHPUnit 4 or PHP < 7.1 (or both) fail to write error_log calls to stdout, even
// with php's 'php://stdout' stream. Forcing error logs to write to /dev/stdout
// works around this
ini_set('error_log', '/dev/stdout');

$client = require __DIR__ . '/_prelude.php';

echo "Including values in Bugsnag's errorReportingLevel that are not in error_reporting should log a warning\n";
error_reporting(E_ALL & ~E_WARNING & ~E_USER_DEPRECATED);
$client->setErrorReportingLevel(E_ERROR | E_WARNING | E_NOTICE | E_USER_DEPRECATED);

echo "\nSetting Bugsnag's errorReportingLevel to null should be fine\n";
$client->setErrorReportingLevel(null);

echo "\nSetting Bugsnag's errorReportingLevel to a subset of error_reporting should be fine\n";
$client->setErrorReportingLevel(E_ERROR);

echo "\nSetting Bugsnag's errorReportingLevel to 0 should be fine\n";
$client->setErrorReportingLevel(0);

echo "\nSetting Bugsnag's errorReportingLevel to exclusively E_WARNING (which is missing from error_reporting) should log a warning\n";
$client->setErrorReportingLevel(E_WARNING);

echo "\nSetting both error_reporing and Bugsnag's errorReportingLevel to exclusively E_WARNING should be fine\n";
error_reporting(E_WARNING);
$client->setErrorReportingLevel(E_WARNING);
?>
--EXPECTF--
Including values in Bugsnag's errorReportingLevel that are not in error_reporting should log a warning
[%s] Bugsnag Warning: errorReportingLevel cannot contain values that are not in error_reporting. Any errors of these levels will be ignored: E_WARNING, E_USER_DEPRECATED.

Setting Bugsnag's errorReportingLevel to null should be fine

Setting Bugsnag's errorReportingLevel to a subset of error_reporting should be fine

Setting Bugsnag's errorReportingLevel to 0 should be fine

Setting Bugsnag's errorReportingLevel to exclusively E_WARNING (which is missing from error_reporting) should log a warning
[%s] Bugsnag Warning: errorReportingLevel cannot contain values that are not in error_reporting. Any errors of these levels will be ignored: E_WARNING.

Setting both error_reporing and Bugsnag's errorReportingLevel to exclusively E_WARNING should be fine
