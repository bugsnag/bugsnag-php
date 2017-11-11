<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/

$hook['pre_system'] = function(){
  require_once 'vendor/autoload.php';

  // Automatically send unhandled errors to your Bugsnag dashboard:
  $GLOBALS['bugsnag'] = Bugsnag\Client::make("your-api-key-here");
  Bugsnag\Handler::register($GLOBALS['bugsnag']);

  // Manually send an error (you can use this to test your integration)
  $GLOBALS['bugsnag']->notifyError('ErrorType', 'A wild error appeared!');
}

?>
