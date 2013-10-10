<?php

class errorTest extends PHPUnit_Framework_TestCase {
    protected $config;
    protected $error;

    protected function setUp(){
        $this->config = new Bugsnag_Configuration();
    }

    public function testMetaData() {
        $this->config->metaData = array("Testing" => array("globalArray" => "hi"));
        $this->error = Bugsnag_Error::fromNamedError($this->config, "Name", "Message");

        $this->assertEquals($this->error->metaData["Testing"]["globalArray"], "hi");
    }

    public function testMetaDataFunction() {
        $this->config->metaDataFunction = "bugsnag_metadata";
        $this->error = Bugsnag_Error::fromNamedError($this->config, "Name", "Message");

        $this->assertEquals($this->error->metaData["Testing"]["globalFunction"], "hello");
    }

    public function testBeforeNotifyFunction() {
        $this->config->beforeNotifyFunction = "bugsnag_before_notify";
        $this->error = Bugsnag_Error::fromNamedError($this->config, "Name", "Message");

        $this->assertEquals($this->error->metaData["Testing"]["callbackFunction"], "blergh");
    }

    public function testMetaDataMerging() {
        $this->config->metaData = array("Testing" => array("globalArray" => "hi"));
        $this->config->metaDataFunction = "bugsnag_metadata";
        $this->config->beforeNotifyFunction = "bugsnag_before_notify";

        $this->error = Bugsnag_Error::fromNamedError($this->config, "Name", "Message");
        $this->error->setMetaData(array("Testing" => array("localArray" => "yo")));

        $this->assertEquals($this->error->metaData["Testing"]["globalArray"], "hi");
        $this->assertEquals($this->error->metaData["Testing"]["globalFunction"], "hello");
        $this->assertEquals($this->error->metaData["Testing"]["localArray"], "yo");
        $this->assertEquals($this->error->metaData["Testing"]["callbackFunction"], "blergh");
    }

    public function testShouldIgnore() {
        $this->config->errorReportingLevel = E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED;
        $this->error = Bugsnag_Error::fromPHPError($this->config, E_NOTICE, "Broken", "file", 123);

        $this->assertEquals($this->error->shouldIgnore(), TRUE);
    }

    public function testShouldNotIgnore() {
        $this->config->errorReportingLevel = E_ALL;
        $this->error = Bugsnag_Error::fromPHPError($this->config, E_NOTICE, "Broken", "file", 123);

        $this->assertEquals($this->error->shouldIgnore(), FALSE);
    }

    public function testFiltering() {
        $this->error = Bugsnag_Error::fromNamedError($this->config, "Name", "Message");
        $this->error->setMetaData(array("Testing" => array("password" => "123456")));

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['metaData']['Testing']['password'], '[FILTERED]');
    }
}

function bugsnag_metadata() {
    return array("Testing" => array("globalFunction" => "hello"));
}

function bugsnag_before_notify($error) {
    $error->setMetaData(array("Testing" => array("callbackFunction" => "blergh")));
}

?>