<?php

namespace Bugsnag;

use Exception;
use GuzzleHttp\ClientInterface;
use RuntimeException;

class HttpClient
{
    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    protected $config;

    /**
     * The guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $guzzle;

    /**
     * The queue of errors to send.
     *
     * @var \Bugsnag\Error[]
     */
    protected $queue = [];

    /**
     * Create a new http client instance.
     *
     * @param \Bugsnag\Configuration      $config the configuration instance
     * @param \GuzzleHttp\ClientInterface $guzzle the guzzle client instance
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
     * @param \Bugsnag\Error $error the bugsnag error instance
     *
     * @return void
     */
    public function queue(Error $error)
    {
        $this->queue[] = $error;
    }

    /**
     * Deliver everything on the queue to Bugsnag.
     *
     * @return void
     */
    public function send()
    {
        if (!$this->queue) {
            return;
        }

        $this->postJson('/', $this->build());

        $this->queue = [];
    }

    /**
     * Build the request data to send.
     *
     * @return array
     */
    protected function build()
    {
        $events = [];

        foreach ($this->queue as $error) {
            $errorArray = $error->toArray();

            if (!is_null($errorArray)) {
                $events[] = $errorArray;
            }
        }

        return [
            'apiKey' => $this->config->getApiKey(),
            'notifier' => $this->config->getNotifier(),
            'events' => $events,
        ];
    }

    /**
     * Post the given data to Bugsnag in json form.
     *
     * @param string $url  the url to hit
     * @param array  $data the data send
     *
     * @return void
     */
    protected function postJSON($url, $data)
    {
        // Try to send the whole lot, or without the meta data for the first
        // event. If failed, try to send the first event, and then the rest of
        // them, recursively. Decrease by a constant and concquer if you like.
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
