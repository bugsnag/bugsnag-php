# Bugsnag-PHP example

This guide explains how to set up Bugsnag-php in your base PHP project.  It demonstrates configuring Bugsnag with your PHP project, using handled and unhandled exceptions, how to add additional data to Bugsnag error reports, and using PHP.ini to capture all errors across your project.

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

## Install dependencies

Bugsnag is most commonly installed using the [Composer](https://getcomposer.org/) dependency manager.

```shell
composer install
```

As an alternative, Bugsnag can also be used directly from a `.phar` file. The example `phar.php` demonstrates this, with full instructions below.

## Set your API key

This can be accomplished in one of two ways:

### Running the examples

Each of the examples can be run using the command:
```shell
BUGSNAG_API_KEY=<YOUR_API_KEY> php <FILE_NAME>
```
After running each example visit [the Bugsnag dashboard](https://app.bugsnag.com) to see the results.

1. `unhandled.php` contains a basic setup to capture any unhandled exceptions raised in the script. It creates an instance of the Bugsnag `client`, then registers the exception handling functions using:
```php
Bugsnag\Handler::register($bugsnag);
```

2. `callback.php` adds a callback to the Bugsnag `client` to add metadata to each automatically notified exception.  This can be seen by selecting the `ACCOUNT` tab on the exception in the Bugsnag dashboard.

3. `handled.php` is an example of how to manually notify bugsnag of an issue with a closure to add additional metadata to the notification.

4. `catchall.php` throws an error without setting up any Bugsnasg specific configuration.  Executing this file would throw a syntax error without sending an exception to Bugsnag.

    However by creating a shutdown function and registering it to PHP (seen in `prepend.php`) we can ensure that any fatal shutdown errors are sent to Bugsnag as a notification.

    We can ensure this file is executed before the main script using the `php.ini` file `auto_prepend_file` attribute.  This will also allow Bugsnag to be notified of errors and exceptions that occur before the full Bugsnag setup such as the syntax error thrown by `catchall.php`.

    Consult the [php documentation](http://php.net/manual/en/configuration.file.php) on where to include your `php.ini` file.

    To execute the example using the `php.ini` file use the command:

```shell
BUGSNAG_API_KEY=<YOUR_API_KEY> php -c php.ini catchall.php
```

5. `phar.php` is identical to `unhandled.php`, but acquires Bugsnag from a `bugsnag.phar` file instead of using composer. This file, and the accompanying `guzzle.phar` file must be downloaded from the appropriate places, [the Bugsnag-php releases page](https://github.com/bugsnag/bugsnag-php/releases) and [the Guzzle releases page](https://github.com/guzzle/guzzle/releases).

    These files must be in this directory for the `phar.php` example to function correctly.

    For more information about `.phar` files see [the PHP manual](https://www.php.net/manual/en/intro.phar.php).
