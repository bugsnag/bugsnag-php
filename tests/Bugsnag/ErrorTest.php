<?php

class errorTest extends PHPUnit_Framework_TestCase {
    public function testMetaDataMerging() {
        $config = new Bugsnag\Configuration();
        $config->metaData = array("Testing" => array("globalArray" => "hi"));
        $config->metaDataFunction = "bugsnag_metadata";

        $error = Bugsnag\Error::fromNamedError($config, "Name", "Message");
        $error->setMetaData(array("Testing" => array("localArray" => "yo")));

        $this->assertEquals($error->metaData["Testing"]["globalArray"], "hi");
        $this->assertEquals($error->metaData["Testing"]["globalFunction"], "hello");
        $this->assertEquals($error->metaData["Testing"]["localArray"], "yo");
    }
}

function bugsnag_metadata() {
    return array("Testing" => array("globalFunction" => "hello"));
}

?>