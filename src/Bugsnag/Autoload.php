<?php

spl_autoload_register('__autoload');
function __autoload($class) {
    if(strpos($class, 'Bugsnag_') !== 0) {
        return;
    }

    // Approximate namespaces (to maintain compatibility with PHP 5.2)
    $file = dirname(__FILE__).DIRECTORY_SEPARATOR.str_replace("Bugsnag_", '', $class).".php";
    if(is_file($file)) {
        require_once($file);
    }
}

?>