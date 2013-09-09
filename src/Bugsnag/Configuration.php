<?php namespace Bugsnag;

class Configuration {
    private static $DEFAULT_ENDPOINT = "notify.bugsnag.com";

    public $apiKey;
    public $autoNotify = true;
    public $useSSL = true;
    public $endpoint;
    public $notifyReleaseStages;
    public $filters = array('password');
    public $projectRoot;

    public $context;
    public $userId;
    public $releaseStage = 'production';
    public $appVersion;
    public $osVersion;

    public $metaData;
    public $metaDataFunction;
    public $projectRootRegex;
    public $errorReportingLevel;

    public function __construct() {
        $this->endpoint = self::$DEFAULT_ENDPOINT;
    }

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