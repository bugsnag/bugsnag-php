<?php

abstract class Bugsnag_TestCase extends PHPUnit_Framework_TestCase {
    protected function getError($name="Name", $message="Message") {
        return Bugsnag_Error::fromNamedError($this->config, $this->diagnostics, $name, $message);
    }
}
