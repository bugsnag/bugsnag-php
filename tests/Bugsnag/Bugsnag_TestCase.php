<?php

abstract class Bugsnag_TestCase extends PHPUnit_Framework_TestCase
{
    protected function getError($name = "Name", $message = "Message")
    {
        return Bugsnag_Error::fromNamedError($this->config, $this->diagnostics, $name, $message);
    }

    protected function getFixture($file)
    {
        return json_decode(file_get_contents(dirname(__FILE__)."/../fixtures/".$file), true);
    }
}
