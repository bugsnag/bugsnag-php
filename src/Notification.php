<?php

namespace Bugsnag;

use Exception;
use GuzzleHttp\ClientInterface;
use RuntimeException;

class Notification
{
    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    protected $config;

    /**
     * The pipeline instance.
     *
     * @var \Bugsnag\Pipeline
     */
    protected $pipeline;

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
     * @param \Bugsnag\Configuration      $config   the configuration instance
     * @param \Bugsnag\Pipeline           $pipeline the notification pipleine instance
     * @param \GuzzleHttp\ClientInterface $guzzle   the guzzle client instance
     *
     * @return void
     */
    public function __construct(Configuration $config, Pipeline $pipeline, ClientInterface $guzzle)
    {
        $this->config = $config;
        $this->pipeline = $pipeline;
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
        return $this->pipeline->execute($error, function ($error) use ($passedMetaData) {
            $error->setMetaData($passedMetaData);

            $this->errorQueue[] = $error;

            return true;
        });
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
        $this->postJson('/', $this->toArray());

        // Clear the error queue
        $this->errorQueue = [];
    }

    /**
     * Post the given data to Bugsnag in json form.
     *
     * @param string $url  the url to hit
     * @param array  $data the data send
     *
     * @return void
     */
    public function postJSON($url, $data)
    {
        // Try to send the whole lot, or without the meta data for the first
        // event. If failed, try to send the first event, and then the rest of
        // them, revursively. Decrease by a constant and concquer if you like.
        // Note that the base case is satisfied as soon as the payload is small
        // enought to send, or when it's simply discarded.
        try {
            $normalized = $this->normalize($data);
        } catch (RuntimeException $e) {
            if (count($data['events']) > 1) {
                $event = array_shift($data['events']);
                $this->postJSON($url, array_merge($data, ['events' => [$event]]));
                $this->postJSON($url, $data);
            } else {
                error_log('Bugsnag Warning: '.$e->getMessage());
            }

            return;
        }

        // Send via guzzle and log any failures
        try {
            $this->guzzle->request('POST', $url, ['json' => $normalized]);
        } catch (Exception $e) {
            error_log('Bugsnag Warning: Couldn\'t notify. '.$e->getMessage());
        }
    }

    /**
     * Normalize the given data to ensure it's the correct size.
     *
     * @param array $data the data to normalize
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    protected function normalize(array $data)
    {
        $body = json_encode($data);

        if ($this->length($body) > 500000) {
            unset($data['events'][0]['metaData']);
        }

        $body = json_encode($data);

        if ($this->length($body) > 500000) {
            throw new RuntimeException('Payload too large');
        }

        return $data;
    }

    /**
     * Get the length of the given string in bytes.
     *
     * @param string $str the string to get the length of
     *
     * @return int
     */
    protected function length($str)
    {
        return function_exists('mb_strlen') ? mb_strlen($str, '8bit') : strlen($str);
    }
}
