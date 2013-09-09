<?php

function __autoload($name) {
    require_once dirname(__FILE__) . "/" . str_replace("\\", DIRECTORY_SEPARATOR, $name) . ".php";
}

?>