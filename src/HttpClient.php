<?php

namespace Bugsnag;

use Exception;
use GuzzleHttp\ClientInterface;
use RuntimeException;

class HttpClient
{
    /**
     * The maximum payload size — one megabyte (1024 * 1024).
     */
    const MAX_SIZE = 1048576;

    /**
     * The payload version for the error notification API.
     */
    const NOTIFY_PAYLOAD_VERSION = '4.0';

    /**
     * The payload version for the session API.
     */
    const SESSION_PAYLOAD_VERSION = '1.0';

    /**
     * The payload version for the error notification API.
     *
     * @deprecated Use {self::NOTIFY_PAYLOAD_VERSION} instead.
     */
    const PAYLOAD_VERSION = self::NOTIFY_PAYLOAD_VERSION;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var ClientInterface
     */
    protected $guzzle;

    /**
     * @var array<Report>
     */
    protected $queue = [];

    /**
     * @param Configuration   $config
     * @param ClientInterface $guzzle
     */
    public function __construct(Configuration $config, ClientInterface $guzzle)
    {
        $this->config = $config;
        $this->guzzle = $guzzle;
    }

    /**
     * Add a report to the queue.
     *
     * @param Report $report
     *
     * @return void
     */
    public function queue(Report $report)
    {
        $this->queue[] = $report;
    }

    /**
     * Deliver all errors on the queue to Bugsnag.
     *
     * @return void
     */
    public function send()
    {
        if (!$this->queue) {
            return;
        }

        $events = [];

        foreach ($this->queue as $report) {
            $event = $report->toArray();

            if ($event) {
                $events[] = $event;
            }
        }

        $body = [
            'apiKey' => $this->config->getApiKey(),
            'notifier' => $this->config->getNotifier(),
            'events' => $events,
        ];

        $this->deliverEvents($this->config->getNotifyEndpoint(), $body);

        $this->queue = [];
    }

    /**
     * Send a session data payload to Bugsnag.
     *
     * @param array $payload
     *
     * @return void
     */
    public function sendSessions(array $payload)
    {
        $this->post(
            $this->config->getSessionEndpoint(),
            [
                'json' => $payload,
                'headers' => $this->getHeaders(self::SESSION_PAYLOAD_VERSION),
            ]
        );
    }

    /**
     * Notify Bugsnag of a build.
     *
     * @param array $buildInfo the build information
     *
     * @return void
     */
    public function sendBuildReport(array $buildInfo)
    {
        $app = $this->config->getAppData();

        if (!isset($app['version'])) {
            error_log('Bugsnag Warning: App version is not set. Unable to send build report.');

            return;
        }

        $data = ['appVersion' => $app['version']];

        $sourceControl = [];

        if (isset($buildInfo['repository'])) {
            $sourceControl['repository'] = $buildInfo['repository'];
        }

        if (isset($buildInfo['provider'])) {
            $sourceControl['provider'] = $buildInfo['provider'];
        }

        if (isset($buildInfo['revision'])) {
            $sourceControl['revision'] = $buildInfo['revision'];
        }

        if (!empty($sourceControl)) {
            $data['sourceControl'] = $sourceControl;
        }

        if (isset($buildInfo['builder'])) {
            $data['builderName'] = $buildInfo['builder'];
        } else {
            $data['builderName'] = Utils::getBuilderName();
        }

        if (isset($buildInfo['buildTool'])) {
            $data['buildTool'] = $buildInfo['buildTool'];
        } else {
            $data['buildTool'] = 'bugsnag-php';
        }

        $data['releaseStage'] = $app['releaseStage'];
        $data['apiKey'] = $this->config->getApiKey();

        $this->post($this->config->getBuildEndpoint(), ['json' => $data]);
    }

    /**
     * Notify Bugsnag of a deployment.
     *
     * @param array $data the deployment information
     *
     * @return void
     *
     * @deprecated Use {@see self::sendBuildReport} instead.
     */
    public function deploy(array $data)
    {
        $app = $this->config->getAppData();

        $data['releaseStage'] = $app['releaseStage'];

        if (isset($app['version'])) {
            $data['appVersion'] = $app['version'];
        }

        $data['apiKey'] = $this->config->getApiKey();

        $uri = rtrim($this->config->getNotifyEndpoint(), '/').'/deploy';

        $this->post($uri, ['json' => $data]);
    }

    /**
     * Deliver the given events to the notification API.
     *
     * @param string $uri
     * @param array  $data
     *
     * @return void
     *
     * @deprecated Use {@see self::deliverEvents} instead.
     */
    protected function postJson($uri, array $data)
    {
        $this->deliverEvents($uri, $data);
    }

    /**
     * Deliver the given events to the notification API.
     *
     * @param array $data
     *
     * @return void
     */
    protected function deliverEvents($uri, array $data)
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

                $this->deliverEvents($uri, array_merge($data, ['events' => [$event]]));
                $this->deliverEvents($uri, $data);
            } else {
                error_log('Bugsnag Warning: '.$e->getMessage());
            }

            return;
        }

        try {
            $this->post(
                $uri,
                [
                    'json' => $normalized,
                    'headers' => $this->getHeaders(self::NOTIFY_PAYLOAD_VERSION),
                ]
            );
        } catch (Exception $e) {
            error_log('Bugsnag Warning: Couldn\'t notify. '.$e->getMessage());
        }
    }

    /**
     * Builds the array of headers to send using the given payload version.
     *
     * If no payload version is given, we assume this is for the notify endpoint
     * and so use {@see self::NOTIFY_PAYLOAD_VERSION}.
     *
     * @param string|null $version The payload version this request is for
     *                             Not providing this parameter is deprecated
     *                             and it will be required in the next major version.
     *
     * @return array
     */
    protected function getHeaders($version = null)
    {
        if ($version === null) {
            $version = self::NOTIFY_PAYLOAD_VERSION;
        }

        return [
            'Bugsnag-Api-Key' => $this->config->getApiKey(),
            'Bugsnag-Sent-At' => strftime('%Y-%m-%dT%H:%M:%S'),
            'Bugsnag-Payload-Version' => $version,
        ];
    }

    /**
     * @param string $uri
     * @param array  $options
     *
     * @return void
     */
    protected function post($uri, array $options = [])
    {
        if (method_exists(ClientInterface::class, 'request')) {
            $this->guzzle->request('POST', $uri, $options);
        } else {
            $this->guzzle->post($uri, $options);
        }
    }

    /**
     * Normalize the given data to ensure it's the correct size.
     *
     * @param array $data
     *
     * @throws RuntimeException
     *
     * @return array
     */
    protected function normalize(array $data)
    {
        $body = json_encode($data);

        if ($this->length($body) > static::MAX_SIZE) {
            unset($data['events'][0]['metaData']);
        }

        $body = json_encode($data);

        if ($this->length($body) > static::MAX_SIZE) {
            throw new RuntimeException('Payload too large');
        }

        return $data;
    }

    /**
     * Get the length of the given string in bytes.
     *
     * @param string $str
     *
     * @return int
     */
    protected function length($str)
    {
        return function_exists('mb_strlen') ? mb_strlen($str, '8bit') : strlen($str);
    }
}
