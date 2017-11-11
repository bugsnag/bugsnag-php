# Set up Bugsnag with CodeIgniter

1. Download CodeIgniter:
You can download it here: https://codeigniter.com/download

2. Start it up
If you're using MAMP, move it to htdocs, start MAMP, then go to localhost:8888.
If you're not using MAMP, move the CodeIgniter folder to wherever your web server serves files from.

3. Use Composer to get Bugsnag
$ cd <your-projects-root-folder>
$ composer require "bugsnag/bugsnag:^3.0"

4. Enable 'hooks' in CodeIgniter
Go to '/application/config/config.php'. Search for `$config['enable_hooks']`. Set it to true, so you have: `$config['enable_hooks'] = TRUE;`.

5. Load Bugsnag
Paste this into your hooks.php file:

```
$hook['pre_system'] = function(){
  require_once 'vendor/autoload.php';

  // Automatically send unhandled errors to your Bugsnag dashboard:
  $GLOBALS['bugsnag'] = Bugsnag\Client::make("ceb57130c9b716304a9ff0632b1e4440");
  Bugsnag\Handler::register($GLOBALS['bugsnag']);

  // Manually send an error (you can use this to test your integration)
  // $GLOBALS['bugsnag']->notifyError('ErrorType', 'global Error');
}
```
