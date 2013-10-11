<?php

class Bugsnag_Configuration {
    public $apiKey;
    public $autoNotify = true;
    public $useSSL = true;
    public $endpoint = 'notify.bugsnag.com';
    public $notifyReleaseStages;
    public $filters = array('password');
    public $projectRoot;
    public $projectRootRegex;

    public $context;
    public $userId;
    public $releaseStage = 'production';
    public $appVersion;
    public $osVersion;

    public $metaData;
    public $beforeNotifyFunction;
    public $errorReportingLevel;

    public function getNotifyEndpoint() {
        return $this->getProtocol()."://".$this->endpoint;
    }

    public function shouldNotify() {
        return is_null($this->notifyReleaseStages) || (is_array($this->notifyReleaseStages) && in_array($this->releaseStage, $this->notifyReleaseStages));
    }

    public function setProjectRoot($projectRoot) {
        $this->projectRoot = $projectRoot;
        $this->projectRootRegex = '/'.preg_quote($projectRoot, '/')."[\\/]?/i";
    }

    private function getProtocol() {
        return $this->useSSL ? "https" : "http";
    }
}

?>