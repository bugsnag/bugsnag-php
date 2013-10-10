<?php

spl_autoload_register('__autoload');
function __autoload($class) {
    if(strpos($class, 'Bugsnag_') !== 0) {
        return;
    }

    // Approximate namespaces (to maintain compatibility with PHP 5.2)
    $file = realpath(dirname(__FILE__).'/../'.str_replace(array('_', "\0"), array('/', ''), $class).'.php');
    if(is_file($file)) {
        require_once($file);
    }
}

?>