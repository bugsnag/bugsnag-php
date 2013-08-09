<?php

class ConfigurationTest extends PHPUnit_Framework_TestCase {
    public function testEndpoint() {
        // Test default endpoint
        $config = new Bugsnag\Configuration();
        $this->assertEquals($config->getNotifyEndpoint(), "https://notify.bugsnag.com");

        // Test non-ssl endpoint
        $config = new Bugsnag\Configuration();
        $config->useSSL = false;
        $this->assertEquals($config->getNotifyEndpoint(), "http://notify.bugsnag.com");

        // Test custom endpoint
        $config = new Bugsnag\Configuration();
        $config->useSSL = false;
        $config->endpoint = "localhost";
        $this->assertEquals($config->getNotifyEndpoint(), "http://localhost");
    }

    public function testShouldNotify() {
        // Test default releaseStage
        $config = new Bugsnag\Configuration();
        $this->assertEquals($config->shouldNotify(), true);

        // Test custom releaseStage
        $config = new Bugsnag\Configuration();
        $config->releaseStage = "staging";
        $this->assertEquals($config->shouldNotify(), true);

        // Test custom notifyReleaseStages
        $config = new Bugsnag\Configuration();
        $config->notifyReleaseStages = array("banana");
        $this->assertEquals($config->shouldNotify(), false);

        // Test custom releaseStage and notifyReleaseStages
        $config = new Bugsnag\Configuration();
        $config->releaseStage = "banana";
        $config->notifyReleaseStages = array("banana");
        $this->assertEquals($config->shouldNotify(), true);
    }
}

?>