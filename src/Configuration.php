<?php

namespace Bugsnag;

class Configuration
{
    public static $DEFAULT_TIMEOUT = 10;
    public static $DEFAULT_ENDPOINT = 'https://notify.bugsnag.com';

    public $apiKey;
    public $autoNotify = true;
    public $batchSending = true;
    public $endpoint;
    public $notifyReleaseStages;
    public $filters = ['password'];
    public $projectRoot;
    public $projectRootRegex;
    public $proxySettings = [];
    public $notifier = [
        'name' => 'Bugsnag PHP (Official)',
        'version' => '3.0.0',
        'url' => 'https://bugsnag.com',
    ];
    public $sendEnvironment = false;
    public $sendCookies = true;
    public $sendSession = true;
    public $sendCode = true;
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

    public $curlOptions = [];

    public $debug = false;

    public function __construct()
    {
        $this->timeout = self::$DEFAULT_TIMEOUT;
    }

    public function getNotifyEndpoint()
    {
        return $this->endpoint ?: self::$DEFAULT_ENDPOINT;
    }

    public function shouldNotify()
    {
        return is_null($this->notifyReleaseStages) || (is_array($this->notifyReleaseStages) && in_array($this->releaseStage, $this->notifyReleaseStages));
    }

    public function shouldIgnoreErrorCode($code)
    {
        if (isset($this->errorReportingLevel)) {
            return !($this->errorReportingLevel & $code);
        } else {
            return !(error_reporting() & $code);
        }
    }

    public function setProjectRoot($projectRoot)
    {
        $this->projectRoot = $projectRoot;
        $this->projectRootRegex = '/'.preg_quote($projectRoot, '/').'[\\/]?/i';
        if (is_null($this->stripPath)) {
            $this->setStripPath($projectRoot);
        }
    }

    public function setStripPath($stripPath)
    {
        $this->stripPath = $stripPath;
        $this->stripPathRegex = '/'.preg_quote($stripPath, '/').'[\\/]?/i';
    }

    public function get($prop, $default = null)
    {
        $configured = $this->$prop;

        if (is_array($configured) && is_array($default)) {
            return array_merge($default, $configured);
        } else {
            return $configured ? $configured : $default;
        }
    }
}
