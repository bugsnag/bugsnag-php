# Set up Bugsnag with CodeIgniter

You will need a Bugsnag account. If you don't have one, go make one [here](https://www.bugsnag.com/platforms/php/)!

#### 1. Download CodeIgniter:
You can download it here: https://codeigniter.com/download

#### 2. Start it up
If you're using MAMP, move it to htdocs, start MAMP, then go to localhost:8888.
If you're not using MAMP, move the CodeIgniter folder to wherever your web server serves files from.

#### 3. Use Composer to get Bugsnag
```
$ cd <your-projects-root-folder>
$ composer require "bugsnag/bugsnag:^3.0"
```

#### 4. Enable 'hooks' in CodeIgniter
Go to '/application/config/config.php'. Search for `$config['enable_hooks']`. Set it to true, so you have: `$config['enable_hooks'] = TRUE;`.

#### 5. Load Bugsnag
Paste this into your /application/config/hooks.php file:

```
$hook['pre_system'] = function(){
  require_once 'vendor/autoload.php';

  // Automatically send unhandled errors to your Bugsnag dashboard:
  $GLOBALS['bugsnag'] = Bugsnag\Client::make("my-secret-key");
  Bugsnag\Handler::register($GLOBALS['bugsnag']);

  // Manually send an error (you can use this to test your integration)
  // $GLOBALS['bugsnag']->notifyError('ErrorType', 'A wild error appeared!');
}
```

### You're done!
Cause an error in your application, you should see it appear on your Bugsnag dashboard. You can manually log an error with:  `$GLOBALS['bugsnag']->notifyError('ErrorType', 'A wild error appeared!');`

#### Can't find your API key?
Go to your dashboard: https://app.bugsnag.com. Be sure you're on the right project! Your project name is in the top-left corner.

Then click "Settings" in the top left corner.

You will see a section called "Notifier API Key". That is your API key.
