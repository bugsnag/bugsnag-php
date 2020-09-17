# Bugsnag PHP test suite

## Writing tests

Bugsnag PHP is currently compatible with PHP 5.5 - 7.4. This places restrictions on our test suite, as we have to support PHPUnit 4, 7 and 9 in order to run tests against all of our supported PHP versions.

All unit tests must extend `Bugsnag\Tests\TestCase`.

In order to support a wide range of PHP and PHPUnit versions, there are some constraints that our tests must obey. Some of these are listed below, but others may not have been encountered yet — our CI will fail if an unsupported feature is used.

### Assertions

Some assertions have been replaced in PHPUnit and so are not usable in our test suite. Replacement assertions that are compatible across PHPUnit versions are available on the `Bugsnag\Tests\Assert` class. This affects:

- `assertRegExp`/`assertMatchesRegularExpression` — use `Assert::matchesRegularExpression` instead
- `assertInternalType`/`assertIs<Type>` — use `Assert::isType` instead

### Set up and tear down

PHPUnit's `setUp` and `tearDown` methods aren't usable because they require a `void` return type in newer versions of PHPUnit. This isn't possible to add while also being compatible with PHP 5.

Therefore we have to use [`@before`](https://phpunit.readthedocs.io/en/9.3/annotations.html#before) and [`@after`](https://phpunit.readthedocs.io/en/9.3/annotations.html#after) annotations instead. By convention, these are placed on methods named `beforeEach` and `afterEach`.

### Expecting exceptions

PHPUnit has different methods to expect exceptions in different versions. Instead of using PHPUnit methods, `Bugsnag\Tests\TestCase` has an `expectedException` method, which is compatible with all supported versions of PHPUnit.
