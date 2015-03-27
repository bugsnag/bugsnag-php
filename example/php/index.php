<?php

require_once '/Users/snmaynard/Projects/bugsnag/notifiers/php/build/bugsnag.phar';

$bugsnag = new Bugsnag_Client("066f5ad3590596f9aa8d601ea89af845");
$bugsnag->notifyError("Broken", "Something broke", array('tab' => array('paying' => true, 'object' => (object)array('key' => 'value'), 'null' => NULL, 'string' => "yo", "int" => 4)));

?>
