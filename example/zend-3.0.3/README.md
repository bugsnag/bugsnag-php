# Set up Bugsnag with Zend

This example demonstrates how to set up Bugsnag with the Zend web framework for PHP.

You will need a Bugsnag account. Create a new one [here](https://www.bugsnag.com/platforms/php/)!

## Steps for setting up Bugsnag with Zend Framework

1. Create a Zend project
`$ composer create-project zendframework/skeleton-application project`

2. Install Bugsnag
`$ composer require "bugsnag/bugsnag:^3.0"`

3. Create and register a Bugsnag client
In `public/index.php`, add the following below `include __DIR__ . '/../vendor/autoload.php';`
  ```php?start
  $GLOBALS['bugsnag'] = \Bugsnag\Client::make("your-api-key-here");
  \Bugsnag\Handler::register($GLOBALS['bugsnag']);
  ```
4. Add Bugsnag to your module
   In your modules `Module.php` file, found at `module/Application/src/Module.php`, add `onBootstrap`
   to your `Module` class.

  ```php
  class Module
  {
      public function onBootstrap(\Zend\Mvc\MvcEvent $event)
      {
          $bugsnag = $GLOBALS['bugsnag'];
          $sharedManager = $event->getApplication()->getEventManager()->getSharedManager();
          $sharedManager->attach('Zend\Mvc\Application', 'dispatch.error', function($exception) use ($bugsnag) {
              if ($exception->getParam('exception')) {
                  $bugsnag->notifyException($exception->getParam('exception'));
                  return false;
              }
          });
      }
  }
  ```

## Steps for running this example
1. Use Composer to install dependencies
  ```php
  $ composer install
  ```

2. Start your webserver

   - Locally:
     ```shell
     php -S 0.0.0.0:3000 -t public/ public/index.php
     ```

   - In docker with docker-composer:
     ```shell
     docker-composer up
     ```

3. View the server by visiting `http://localhost:3000` in your browser.
