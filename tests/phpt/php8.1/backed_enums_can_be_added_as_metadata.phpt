--TEST--
Backed enums can be added as metadata
--FILE--
<?php
namespace Some\Namespace;

enum StringBackedEnum: string {
    case Admin = 'admin';
    case User = 'user';
}

enum IntBackedEnum: int {
    case Square = 1;
    case Circle = 2;
}

$client = require __DIR__ . '/../_prelude.php';

$client->setMetaData([
    'data' => [
        'admin' => StringBackedEnum::Admin,
        'user' => StringBackedEnum::User,
        'square' => IntBackedEnum::Square,
        'circle' => IntBackedEnum::Circle,
    ],
]);

echo "Backed enums should be stored as objects\n";
var_dump($client->getMetaData()['data']);
echo "\n";

$client->notifyException(new \Exception('hello'), function (\Bugsnag\Report $report): void {
    echo "Backed enums should be converted to string representation when serialised\n";
    var_dump($report->toArray()['metaData']['data']);
    echo "\n";
});
?>
--SKIPIF--
<?php
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    echo 'SKIP — this test requires PHP 8.1+ for enum support';
}
?>
--EXPECTF--
Backed enums should be stored as objects
array(4) {
  ["admin"]=>
  enum(Some\Namespace\StringBackedEnum::Admin)
  ["user"]=>
  enum(Some\Namespace\StringBackedEnum::User)
  ["square"]=>
  enum(Some\Namespace\IntBackedEnum::Square)
  ["circle"]=>
  enum(Some\Namespace\IntBackedEnum::Circle)
}

Backed enums should be converted to string representation when serialised
array(4) {
  ["admin"]=>
  string(46) "Some\Namespace\StringBackedEnum::Admin (admin)"
  ["user"]=>
  string(44) "Some\Namespace\StringBackedEnum::User (user)"
  ["square"]=>
  string(40) "Some\Namespace\IntBackedEnum::Square (1)"
  ["circle"]=>
  string(40) "Some\Namespace\IntBackedEnum::Circle (2)"
}

Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - hello
