<?php

abstract class Bugsnag_TestCase extends PHPUnit_Framework_TestCase {
    protected function getError($name="Name", $message="Message") {
        $error = new Bugsnag_Error($this->config, $this->diagnostics);
        $error->setName($name)->setMessage($message);
        return $error;
    }
}
