# Set up Bugsnag with Zend

This example demonstrates how to set up Bugsnag with the Zend web framework for PHP.  Before test

You will need a Bugsnag account. Create a new one [here](https://www.bugsnag.com/platforms/php/)!

## Steps for setting up Bugsnag with Zend Framework

1. Create a Zend project
`$ composer create-project zendframework/skeleton-application project`

2. Install Bugsnag
`$ composer require "bugsnag/bugsnag:^3.0"`

3. Add Bugsnag
In `public/index.php`, add the following below `include __DIR__ . '/../vendor/autoload.php';`

```php
$GLOBALS['bugsnag'] = Bugsnag\Client::make("your-api-key-here");
Bugsnag\Handler::register($GLOBALS['bugsnag']);
```

## Steps for running this example
1. Use Composer to install dependencies
```php
$ composer install
```

2. Set your API key in `public/index.php`:
```php
$GLOBALS['bugsnag'] = Bugsnag\Client::make("your-api-key-here");
```

3. Start your webserver

- Locally:
  ```shell
  php -S 0.0.0.0:3000 -t public/ public/index.php
  ```

- In docker with docker-composer:
  ```shell
  docker-composer up
  ```

4. View the server by visiting `http://localhost:3000` in your browser
