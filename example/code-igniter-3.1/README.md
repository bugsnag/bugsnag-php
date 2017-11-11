# Set up Bugsnag with CodeIgniter

You will need a Bugsnag account. Create a new one [here](https://www.bugsnag.com/platforms/php/)!

## Running this app

1. Use Composer to install dependencies

   ```
   $ composer install
   ```

2. Set your API key in `application/config/hooks.php`:

   ```
   $GLOBALS['bugsnag'] = Bugsnag\Client::make("your-api-key-here");
   ```


3. Start your webserver

   For [MAMP](https://www.mamp.info/en/documentation/):
   * Start MAMP
   * Move this directory to htdocs (on macOS this is `/Applications/MAMP/htdocs`):
     ```
     cp -r code-igniter-3.1/* /Applications/MAMP/htdocs/
     ```
   * Open http://localhost:8888.

4. You're done! You should see a new error on your Bugsnag dashboard.


## Configuration steps

These are the steps used to create this project.

### Use composer to require dependencies

```shell
$ composer require 'bugsnag/bugsnag:^3.0' # install Bugsnag
$ composer require 'bcit-ci/CodeIgniter:^3.0' # install CodeIgniter
```

### Change the 'system' load path

Edit `index.php`, changing `$system_path` to the vendor path:

```php
system_path = 'vendor/bcit-ci/codeigniter/system';
```


### Enable 'hooks' in CodeIgniter

Edit `application/config/config.php` changing the `enable_hooks` config to true:

```php
$config['enable_hooks'] = TRUE;
```

### Load Bugsnag
Paste this into your `application/config/hooks.php` file:

```php
$hook['pre_system'] = function(){
  require_once 'vendor/autoload.php';

  // Automatically send unhandled errors to your Bugsnag dashboard:
  $GLOBALS['bugsnag'] = Bugsnag\Client::make("my-secret-key");
  Bugsnag\Handler::register($GLOBALS['bugsnag']);

  // Manually send an error (you can use this to test your integration)
  // $GLOBALS['bugsnag']->notifyError('ErrorType', 'A wild error appeared!');
}
```


## FAQ

#### Can't find your API key?

Go to your dashboard: https://app.bugsnag.com. Be sure you're on the right project! Your project name is in the top-left corner.

Then click "Settings" in the top left corner.

You will see a section called "Notifier API Key". That is your API key.

#### Don't have Composer?

Composer's website has installation instructions [here](https://getcomposer.org/download/).

For Mac, you can use [Homebrew](https://brew.sh):

```
brew install homebrew/php/composer
```

#### Getting an 'unexpected end of file' error?

You may need to add a `?>` to your hook.php file.
More specifically, you should check your `short_open_tag` setting: https://stackoverflow.com/questions/13990681/php-parse-error-syntax-error-unexpected-end-of-file-in-a-codeigniter-view
