<?php

namespace Bugsnag;

use InvalidArgumentException;

class Configuration
{
    public $apiKey;
    public $autoNotify = true;
    public $batchSending = true;
    public $notifyReleaseStages;
    public $filters = ['password'];
    public $projectRoot;
    public $projectRootRegex;
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

    /**
     * Create a new config instance.
     *
     * @param string   $apiKey  your bugsnag api key
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function __construct($apiKey)
    {
        if (!is_string($apiKey)) {
            throw new InvalidArgumentException('Bugsnag Error: Invalid API key');
        }

        $this->apiKey = $apiKey;
    }

    /**
     * Should we notify?
     *
     * @return bool
     */
    public function shouldNotify()
    {
        return is_null($this->notifyReleaseStages) || (is_array($this->notifyReleaseStages) && in_array($this->releaseStage, $this->notifyReleaseStages));
    }

    /**
     * Should we ignore the given error code?
     *
     * @param int $code the error code
     *
     * @return bool
     */
    public function shouldIgnoreErrorCode($code)
    {
        if (isset($this->errorReportingLevel)) {
            return !($this->errorReportingLevel & $code);
        } else {
            return !(error_reporting() & $code);
        }
    }

    /**
     * Set the project root.
     *
     * @param string $projectRoot the project root path
     *
     * @return void
     */
    public function setProjectRoot($projectRoot)
    {
        $this->projectRoot = $projectRoot;
        $this->projectRootRegex = '/'.preg_quote($projectRoot, '/').'[\\/]?/i';
        if (is_null($this->stripPath)) {
            $this->setStripPath($projectRoot);
        }
    }

    /**
     * Set the strip path.
     *
     * @param string $stripPath the absolute strip path
     *
     * @return void
     */
    public function setStripPath($stripPath)
    {
        $this->stripPath = $stripPath;
        $this->stripPathRegex = '/'.preg_quote($stripPath, '/').'[\\/]?/i';
    }

    /**
     * Get the given configuration.
     *
     * @param string $prop    the property to get
     * @param mixed  $default the value to fallback to
     *
     * @return mixed
     */
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
