<?php

namespace Bugsnag;

use Bugsnag\Request\ResolverInterface;
use Exception;
use GuzzleHttp\ClientInterface;

class Notification
{
    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    protected $config;

    /**
     * The request resolver instance.
     *
     * @var \Bugsnag\Request\ResolverInterface
     */
    protected $resolver;

    /**
     * The guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $guzzle;

    /**
     * The queue of errors to send to Bugsnag.
     *
     * @var \Bugsnag\Error[]
     */
    protected $errorQueue = [];

    /**
     * Create a new notification instance.
     *
     * @param \Bugsnag\Configuration             $config   the configuration instance
     * @param \Bugsnag\Request\ResolverInterface $resolver the request resolver instance
     * @param \GuzzleHttp\ClientInterface        $guzzle   the guzzle client instance
     *
     * @return void
     */
    public function __construct(Configuration $config, ResolverInterface $resolver, ClientInterface $guzzle)
    {
        $this->config = $config;
        $this->resolver = $resolver;
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

        $request = $this->resolver->resolve();

        // Add request meta-data to error
        if ($request->isRequest()) {
            $error->setMetaData($request->getMetaData());
        }

        // Session Tab
        if ($this->config->sendSession && $request->getSessionData()) {
            $error->setMetaData(['session' => $request->getSessionData()]);
        }

        // Cookies Tab
        if ($this->config->sendCookies && $request->getCookieData()) {
            $error->setMetaData(['cookies' => $request->getCookieData()]);
        }

        // Add environment meta-data to error
        if ($this->config->sendEnvironment && $_ENV) {
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
     * @return void
     */
    public function deliver()
    {
        if (empty($this->errorQueue)) {
            return;
        }

        // Post the request to bugsnag
        try {
            $this->guzzle->request('POST', '/', ['json' => $this->toArray()]);
        } catch (Exception $e) {
            error_log('Bugsnag Warning: Couldn\'t notify. '.$e->getMessage());
        }

        // Clear the error queue
        $this->errorQueue = [];
    }
}
