<?php

class errorTest extends PHPUnit_Framework_TestCase {
    protected $config;
    protected $diagnostics;
    protected $error;

    protected function setUp(){
        $this->config = new Bugsnag_Configuration();
        $this->diagnostics = new Bugsnag_Diagnostics($this->config);
    }

    protected function getError() {
        $error = new Bugsnag_Error($this->config, $this->diagnostics);
        $error->setName("Name")->setMessage("Message");
        return $error;
    }

    public function testMetaData() {
        $this->error = $this->getError();
        $this->error->setMetaData(array("Testing" => array("globalArray" => "hi")));

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['metaData']["Testing"]["globalArray"], "hi");
    }

    public function testMetaDataMerging() {
        $this->error = $this->getError();
        $this->error->setMetaData(array("Testing" => array("globalArray" => "hi")));
        $this->error->setMetaData(array("Testing" => array("localArray" => "yo")));

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['metaData']["Testing"]["globalArray"], "hi");
        $this->assertEquals($errorArray['metaData']["Testing"]["localArray"], "yo");
    }

    public function testShouldIgnore() {
        $this->config->errorReportingLevel = E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED;

        $this->error = $this->getError();
        $this->error->setPHPError(E_NOTICE, "Broken", "file", 123);

        $this->assertEquals($this->error->shouldIgnore(), TRUE);
    }

    public function testShouldNotIgnore() {
        $this->config->errorReportingLevel = E_ALL;
        $this->error = $this->getError();
        $this->error->setPHPError(E_NOTICE, "Broken", "file", 123);

        $this->assertEquals($this->error->shouldIgnore(), FALSE);
    }

    public function testFiltering() {
        $this->error = $this->getError();
        $this->error->setMetaData(array("Testing" => array("password" => "123456")));

        $errorArray = $this->error->toArray();
        $this->assertEquals($errorArray['metaData']['Testing']['password'], '[FILTERED]');
    }
}
