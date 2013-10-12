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

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['metaData']["Testing"]["globalArray"], "hi");
    }

    public function testMetaDataMerging() {
        $this->config->metaData = array("Testing" => array("globalArray" => "hi"));

        $this->error = Bugsnag_Error::fromNamedError($this->config, "Name", "Message");
        $this->error->setMetaData(array("Testing" => array("localArray" => "yo")));

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['metaData']["Testing"]["globalArray"], "hi");
        $this->assertEquals($errorArray['metaData']["Testing"]["localArray"], "yo");
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

?>