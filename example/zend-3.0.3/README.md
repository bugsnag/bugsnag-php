# Set up Bugsnag with CodeIgniter

You will need a Bugsnag account. Create a new one [here](https://www.bugsnag.com/platforms/php/)!

## Steps for setting up Bugsnag with Zend Framework

1. Create a Zend project
`$ composer create-project zendframework/skeleton-application project`

2. Install Bugsnag
`$ composer require "bugsnag/bugsnag:^3.0"`

3. Add Bugsnag
In `public/index.php`, add the following below `include __DIR__ . '/../vendor/autoload.php';`

```
$GLOBALS['bugsnag'] = Bugsnag\Client::make("your-api-key-here");
Bugsnag\Handler::register($GLOBALS['bugsnag']);
$GLOBALS['bugsnag']->notifyError('ErrorType', 'A wild error appeared!');
```

## Steps for running this example
1. Use Composer to install dependencies
```
$ composer install
```

2. Set your API key in `public/index.php`:
```
$GLOBALS['bugsnag'] = Bugsnag\Client::make("your-api-key-here");
```

3. Start your webserver

For [MAMP](https://www.mamp.info/en/documentation/):
  1. Start MAMP
  2. Move this directory to htdocs (on macOS this is `/Applications/MAMP/htdocs`):
  ```
  cp -r code-igniter-3.1/* /Applications/MAMP/htdocs/
  ```
  3. Open http://localhost:8888.
  4. You're done! You should see a new error on your Bugsnag dashboard.
