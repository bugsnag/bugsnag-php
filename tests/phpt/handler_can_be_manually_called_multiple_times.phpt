--TEST--
Bugsnag\Handler can be manually called multiple times
--FILE--
<?php
$client = require __DIR__ . '/_prelude.php';

set_Exception_handler(function () {
    var_dump(func_get_args());
});

$handler = Bugsnag\Handler::register($client);

$handler->exceptionHandler(new Exception('bad things'));
$handler->exceptionHandler(new RuntimeException('real bad things'));
$handler->exceptionHandler(new LogicException('terrible things'));
?>
--EXPECTF--
array(1) {
  [0]=>
  object(Exception)#%d (7) {
    ["message":protected]=>
    string(10) "bad things"
    ["string":"Exception":private]=>
    string(0) ""
    ["code":protected]=>
    int(0)
    ["file":protected]=>
    string(%d) "%s"
    ["line":protected]=>
    int(10)
    ["trace":"Exception":private]=>
    array(0) {
    }
    ["previous":"Exception":private]=>
    NULL
  }
}
array(1) {
  [0]=>
  object(RuntimeException)#%d (7) {
    ["message":protected]=>
    string(15) "real bad things"
    ["string":"Exception":private]=>
    string(0) ""
    ["code":protected]=>
    int(0)
    ["file":protected]=>
    string(%d) "%s"
    ["line":protected]=>
    int(11)
    ["trace":"Exception":private]=>
    array(0) {
    }
    ["previous":"Exception":private]=>
    NULL
  }
}
array(1) {
  [0]=>
  object(LogicException)#%d (7) {
    ["message":protected]=>
    string(15) "terrible things"
    ["string":"Exception":private]=>
    string(0) ""
    ["code":protected]=>
    int(0)
    ["file":protected]=>
    string(%d) "%s"
    ["line":protected]=>
    int(12)
    ["trace":"Exception":private]=>
    array(0) {
    }
    ["previous":"Exception":private]=>
    NULL
  }
}
Guzzle request made (3 events)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - bad things
    - real bad things
    - terrible things
