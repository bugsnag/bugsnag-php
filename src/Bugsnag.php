<?php

spl_autoload_register('__autoload');
function __autoload($name) {
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "Bugsnag" . DIRECTORY_SEPARATOR . str_replace("Bugsnag", "", $name) . ".php";
}

?>