<?php

namespace Bugsnag;

use InvalidArgumentException;

class Configuration
{
    public $apiKey;
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
    public $sendCode = true;
    public $stripPath;
    public $stripPathRegex;

    public $appData = [];

    public $hostname;

    public $metaData;
    public $errorReportingLevel;

    /**
     * Create a new config instance.
     *
     * @param string $apiKey your bugsnag api key
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
        }

        return !(error_reporting() & $code);
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
     * Get the application information.
     *
     * @return array
     */
    public function getAppData()
    {
        return array_merge(['releaseStage' => 'production'], array_filter($this->appData));
    }
}
