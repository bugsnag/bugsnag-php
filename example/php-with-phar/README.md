# Bugsnag-PHP example using a `.phar` file

This guide explains how to set up Bugsnag-php using a downloaded `.phar` file.  It only demonstrates sending an unhandled error to Bugsnag, with other examples relating to more complex configuration in the `php` example folder.

More details about setting up Bugsnag in your PHP project can be found in [the PHP setup instructions in the official documentation](https://docs.bugsnag.com/platforms/php/other/).  Instructions appropriate for more specific platforms including [Laravel](https://docs.bugsnag.com/platforms/php/laravel/), [Symfony](https://docs.bugsnag.com/platforms/php/symfony/), and [Silex](https://docs.bugsnag.com/platforms/php/silex/) can be found [here](https://docs.bugsnag.com/platforms/php/).


## Configuring Bugsnag

There are two methods of setting up the basic Bugsnag `client`:

1. Pass the API KEY to the `make` command of the Bugsnag `client`:
```php
$bugsnag = Bugsnag\Client::make('YOUR_API_KEY');
```

2. Set the environment variable `BUGSNAG_API_KEY` to your API KEY and create the `client` object separately.
```php
$bugsnag = Bugsnag\Client::make();
```

In the examples included the API KEY is being explicitly set in the client, but uses the `BUGSNAG_API_KEY` environment variable.

More configuration options can be found in the [official documentation](https://docs.bugsnag.com/platforms/php/other/configuration-options/).

## Download `.phar` files

As an alternative to composer, Bugsnag can also be used directly from a `.phar` file. This requires that Bugsnag's dependency Guzzle is also present, whether in `.phar` or any other form. In this example, it assume Guzzle is present as the file `guzzle.phar`.

The latest Bugsnag package `bugsnag.phar` can be downloaded from the Bugsnag-php releases page](https://github.com/bugsnag/bugsnag-php/releases), and the latest Guzzle package  can be downloaded from [the Guzzle releases page](https://github.com/guzzle/guzzle/releases).

### Running the example

`phar.php` contains a basic setup to capture any unhandled exceptions raised in the script. It acquires the required Bugsnag library and dependencies from the `.phar` files using the standard `require` or `include` commands:

```php
include "phar://bugsnag.phar";
include "phar://guzzle.phar";
```

These files must be in this directory for the `phar.php` example to function correctly.

For more information about `.phar` files see [the PHP manual](https://www.php.net/manual/en/intro.phar.php).

The example can be run using the command:
```shell
BUGSNAG_API_KEY=<YOUR_API_KEY> php <FILE_NAME>
```
After running the example visit [the Bugsnag dashboard](https://app.bugsnag.com) to see the results.
