<?php

class Bugsnag_Configuration
{
    public $apiKey;
    public $autoNotify = true;
    public $batchSending = true;
    public $useSSL = true;
    public $endpoint = 'notify.bugsnag.com';
    public $timeout = 10;
    public $notifyReleaseStages;
    public $filters = array('password');
    public $projectRoot;
    public $projectRootRegex;
    public $proxySettings = array();
    public $notifier = array(
        'name'    => 'Bugsnag PHP (Official)',
        'version' => '2.2.10',
        'url'     => 'https://bugsnag.com'
    );
    public $stripPath;
    public $stripPathRegex;

    public $context;
    public $type;
    public $user;
    public $releaseStage = 'production';
    public $appVersion;
    public $hostname;

    public $metaData;
    public $beforeNotifyFunction;
    public $errorReportingLevel;

    public function getNotifyEndpoint()
    {
        return $this->getProtocol()."://".$this->endpoint;
    }

    public function shouldNotify()
    {
        return is_null($this->notifyReleaseStages) || (is_array($this->notifyReleaseStages) && in_array($this->releaseStage, $this->notifyReleaseStages));
    }

    public function setProjectRoot($projectRoot)
    {
        $this->projectRoot = $projectRoot;
        $this->projectRootRegex = '/'.preg_quote($projectRoot, '/')."[\\/]?/i";
        if (is_null($stripPath)) {
          $stripPath = $projectRoot;
        }
    }

    public function setStripPath($stripPath)
    {
        $this->stripPath = $stripPath;
        $this->stripPathRegex = '/'.preg_quote($stripPath, '/')."[\\/]?/i";
    }

    public function get($prop, $default=NULL)
    {
        $configured = $this->$prop;

        if (is_array($configured) && is_array($default)) {
            return array_merge($default, $configured);
        } else {
            return $configured ? $configured : $default;
        }
    }

    private function getProtocol()
    {
        return $this->useSSL ? "https" : "http";
    }
}
