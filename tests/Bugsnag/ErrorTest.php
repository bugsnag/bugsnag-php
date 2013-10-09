<?php

class errorTest extends PHPUnit_Framework_TestCase {
    public function testMetaData() {
        $config = new BugsnagConfiguration();
        $config->metaData = array("Testing" => array("globalArray" => "hi"));

        $error = BugsnagError::fromNamedError($config, "Name", "Message");

        $this->assertEquals($error->metaData["Testing"]["globalArray"], "hi");
    }

    public function testMetaDataFunction() {
        $config = new BugsnagConfiguration();
        $config->metaDataFunction = "bugsnag_metadata";

        $error = BugsnagError::fromNamedError($config, "Name", "Message");

        $this->assertEquals($error->metaData["Testing"]["globalFunction"], "hello");
    }

    public function testBeforeNotifyFunction() {
        $config = new BugsnagConfiguration();
        $config->beforeNotifyFunction = "bugsnag_before_notify";

        $error = BugsnagError::fromNamedError($config, "Name", "Message");

        $this->assertEquals($error->metaData["Testing"]["callbackFunction"], "blergh");
    }

    public function testMetaDataMerging() {
        $config = new BugsnagConfiguration();
        $config->metaData = array("Testing" => array("globalArray" => "hi"));
        $config->metaDataFunction = "bugsnag_metadata";
        $config->beforeNotifyFunction = "bugsnag_before_notify";

        $error = BugsnagError::fromNamedError($config, "Name", "Message");
        $error->setMetaData(array("Testing" => array("localArray" => "yo")));

        $this->assertEquals($error->metaData["Testing"]["globalArray"], "hi");
        $this->assertEquals($error->metaData["Testing"]["globalFunction"], "hello");
        $this->assertEquals($error->metaData["Testing"]["localArray"], "yo");
        $this->assertEquals($error->metaData["Testing"]["callbackFunction"], "blergh");
    }
}

function bugsnag_metadata() {
    return array("Testing" => array("globalFunction" => "hello"));
}

function bugsnag_before_notify($error) {
    $error->setMetaData(array("Testing" => array("callbackFunction" => "blergh")));
}

?>