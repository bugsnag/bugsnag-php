# Using Bugsnag with PHP

This example demonstrates how to set up and use Bugsnag with a plain PHP application.
More information about using Bugsnag with PHP can be found [in the official documentation.](https://docs.bugsnag.com/platforms/php/other/)

## Install dependencies

Bugsnag is most commonly installed using the [Composer](https://getcomposer.org/) dependency manager.

```shell
composer install
```

## Set your API key

This can be accomplished in one of two ways:

1. Set the environment variable `BUGSNAG_API_KEY` to your api key before running the examples

2. Pass your api key to the client `make` function:

    ```php
    $client = Bugsnag\Client::make('YOUR API KEY');
    ```

## Automatic error handling

In order to automatically catch exceptions and errors, Bugsnag needs error and exception handlers to be registered.  This can be done by calling the static `register` function provided by the `Bugsnag\Handler`:

```php
Bugsnag\Handler::register($client);
```

## Run the examples

Each example should be run individually and demonstrates some functionality of the Bugsnag PHP notifier and is fully commented.

Run the examples using `php {filename}`, for example:

```shell
php crash.php
```

and check the [Bugsnag dashboard](https://app.bugsnag.com) to see the events and attached data.