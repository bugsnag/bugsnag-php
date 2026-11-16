--TEST--
Bugsnag\Handler should report OOMs triggered by single large allocations
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

ini_set('memory_limit', '5M');

Bugsnag\Handler::register($client);

$a = str_repeat('a', 2147483647);

echo "No OOM!\n";
?>
--SKIPIF--
<?php
$skip = null;

if (PHP_VERSION_ID >= 80500) {
    $skip = 'SKIP — this test has a different output in PHP 8.5+';
}

// Some PHP builds no longer run shutdown functions after an OOM, which makes
// it impossible for Bugsnag to report one, so probe the runtime for support
if ($skip === null) {
    $probe = 'ini_set("memory_limit", "5M"); register_shutdown_function(function () { echo "SHUTDOWN_RAN"; }); str_repeat("a", 2147483647);';
    $output = function_exists('shell_exec') ? shell_exec(escapeshellarg(PHP_BINARY) . ' -d display_errors=0 -r ' . escapeshellarg($probe) . ' 2>&1') : null;
    if (strpos((string) $output, 'SHUTDOWN_RAN') === false) {
        $skip = 'SKIP — this PHP build does not run shutdown functions after an OOM';
    }
}

if ($skip !== null) {
    echo $skip;
}
?>
--EXPECTF--
Fatal error: Allowed memory size of %d bytes exhausted (tried to allocate %d bytes) in %s on line 8
Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - Allowed memory size of %d bytes exhausted (tried to allocate %d bytes)
