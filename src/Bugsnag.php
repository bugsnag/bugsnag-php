<?php

spl_autoload_register('__autoload');
function __autoload($name) {
    require_once dirname(__FILE__) . "/" . str_replace("\\", DIRECTORY_SEPARATOR, $name) . ".php";
}

?>