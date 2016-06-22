<?php

namespace Bugsnag;

use Exception;
use GuzzleHttp\ClientInterface;

class Notification
{
    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    private $config;

    /**
     * The guzzle client instance.
     *
     * @var \Guzzle\ClientInterface
     */
    private $guzzle;

    /**
     * The queue of errors to send to Bugsnag.
     *
     * @var \Bugsnag\Error[]
     */
    private $errorQueue = [];

    /**
     * Create a new notification instance.
     *
     * @param \Bugsnag\Configuration  $config the configuration instance
     * @param \Guzzle\ClientInterface $guzzle the guzzle client instance
     *
     * @return void
     */
    public function __construct(Configuration $config, ClientInterface $guzzle)
    {
        $this->config = $config;
        $this->guzzle = $guzzle;
    }

    /**
     * Add an error to the queue.
     *
     * @param \Bugsnag\Error $config         the bugsnag error instance
     * @param array          $passedMetaData the associated meta data
     *
     * @return bool
     */
    public function addError(Error $error, $passedMetaData = [])
    {
        // Check if this error should be sent to Bugsnag
        if (!$this->config->shouldNotify()) {
            return false;
        }

        // Add global meta-data to error
        $error->setMetaData($this->config->metaData);

        // Add request meta-data to error
        if (Request::isRequest()) {
            $error->setMetaData(Request::getRequestMetaData());
        }

        // Session Tab
        if ($this->config->sendSession && !empty($_SESSION)) {
            $error->setMetaData(['session' => $_SESSION]);
        }

        // Cookies Tab
        if ($this->config->sendCookies && !empty($_COOKIE)) {
            $error->setMetaData(['cookies' => $_COOKIE]);
        }

        // Add environment meta-data to error
        if ($this->config->sendEnvironment && !empty($_ENV)) {
            $error->setMetaData(['Environment' => $_ENV]);
        }

        // Add user-specified meta-data to error
        $error->setMetaData($passedMetaData);

        // Run beforeNotify function (can cause more meta-data to be merged)
        if (isset($this->config->beforeNotifyFunction) && is_callable($this->config->beforeNotifyFunction)) {
            $beforeNotifyReturn = call_user_func($this->config->beforeNotifyFunction, $error);
        }

        // Skip this error if the beforeNotify function returned FALSE
        if (!isset($beforeNotifyReturn) || $beforeNotifyReturn !== false) {
            $this->errorQueue[] = $error;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the array representation.
     *
     * @return array
     */
    public function toArray()
    {
        $events = [];
        foreach ($this->errorQueue as $error) {
            $errorArray = $error->toArray();

            if (!is_null($errorArray)) {
                $events[] = $errorArray;
            }
        }

        return [
            'apiKey' => $this->config->apiKey,
            'notifier' => $this->config->notifier,
            'events' => $events,
        ];
    }

    /**
     * Deliver everything on the queue to Bugsnag.
     *
     * @return array
     */
    public function deliver()
    {
        if (empty($this->errorQueue)) {
            return;
        }

        // Post the request to bugsnag
        try {
            $this->guzzle->request('PUT', '/', ['json' => $data]);
        } catch (Exception $e) {
            error_log('Bugsnag Warning: Couldn\'t notify. '.$e->getMessage());
        }

        // Clear the error queue
        $this->errorQueue = [];
    }
}
