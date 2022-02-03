--TEST--
Pure enums can be added as metadata
--FILE--
<?php
namespace Some\Namespace;

enum MyPureEnum {
    case TheFirstCase;
    case SecondCase;
    case CaseNumberThree;
}

namespace Another\Namespace;

enum AnotherPureEnum {
    case One;
    case Two;
}

$client = require __DIR__ . '/../_prelude.php';

$client->setMetaData([
    'data' => [
        'first case' => \Some\Namespace\MyPureEnum::TheFirstCase,
        'second case' => \Some\Namespace\MyPureEnum::SecondCase,
        'third case' => \Some\Namespace\MyPureEnum::CaseNumberThree,
        'unrelated thing' => 'yes',
        'case one' => \Another\Namespace\AnotherPureEnum::One,
        'case two' => \Another\Namespace\AnotherPureEnum::Two,
    ],
]);

echo "Pure enums should be stored as objects\n";
var_dump($client->getMetaData()['data']);
echo "\n";

$client->notifyException(new \Exception('hello'), function (\Bugsnag\Report $report): void {
    echo "Pure enums should be converted to a string representation when serialised\n";
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
Pure enums should be stored as objects
array(6) {
  ["first case"]=>
  enum(Some\Namespace\MyPureEnum::TheFirstCase)
  ["second case"]=>
  enum(Some\Namespace\MyPureEnum::SecondCase)
  ["third case"]=>
  enum(Some\Namespace\MyPureEnum::CaseNumberThree)
  ["unrelated thing"]=>
  string(3) "yes"
  ["case one"]=>
  enum(Another\Namespace\AnotherPureEnum::One)
  ["case two"]=>
  enum(Another\Namespace\AnotherPureEnum::Two)
}

Pure enums should be converted to a string representation when serialised
array(6) {
  ["first case"]=>
  string(39) "Some\Namespace\MyPureEnum::TheFirstCase"
  ["second case"]=>
  string(37) "Some\Namespace\MyPureEnum::SecondCase"
  ["third case"]=>
  string(42) "Some\Namespace\MyPureEnum::CaseNumberThree"
  ["unrelated thing"]=>
  string(3) "yes"
  ["case one"]=>
  string(38) "Another\Namespace\AnotherPureEnum::One"
  ["case two"]=>
  string(38) "Another\Namespace\AnotherPureEnum::Two"
}

Guzzle request made (1 event)!
* Method: 'POST'
* URI: 'http://localhost/notify'
* Events:
    - hello
