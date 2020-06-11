<?php

namespace Bugsnag\SessionTracker;

use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Exception;
use InvalidArgumentException;

class SessionTracker implements SessionTrackerInterface
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var HttpClient
     */
    protected $http;

    /**
     * An array of session counts.
     *
     * @var array
     */
    protected $sessionCounts = [];

    /**
     * The last time the sessions were delivered.
     *
     * @var int
     */
    protected $lastSent = 0;

    /**
     * The current session.
     *
     * @var array
     */
    protected $currentSession = [];

    /**
     * A function to use when retrying a failed delivery.
     *
     * @var callable|null
     */
    protected $retryFunction = null;

    /**
     * @param Configuration $config
     * @param HttpClient $http
     *
     * @return void
     */
    public function __construct(Configuration $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http = $http;
    }

    /**
     * @return void
     */
    public function startSession()
    {
        $currentTime = strftime('%Y-%m-%dT%H:%M:00');

        $session = [
            'id' => uniqid('', true),
            'startedAt' => $currentTime,
            'events' => [
                'handled' => 0,
                'unhandled' => 0,
            ],
        ];

        $this->setCurrentSession($session);
        $this->incrementSessions($currentTime);
    }

    /**
     * @param array $session
     *
     * @return void
     */
    public function setCurrentSession(array $session)
    {
        $this->currentSession = $session;
    }

    /**
     * @return array
     */
    public function getCurrentSession()
    {
        return $this->currentSession;
    }

    /**
     * @return void
     */
    public function sendSessions()
    {
        $sessions = $this->sessionCounts;
        $this->sessionCounts = [];

        if (count($sessions) === 0 || !$this->config->shouldNotify()) {
            return;
        }

        $payload = $this->constructPayload($sessions);

        $this->lastSent = time();

        try {
            $this->http->sendSessions($payload);
        } catch (Exception $e) {
            error_log('Bugsnag Warning: Couldn\'t notify. '.$e->getMessage());

            if (is_callable($this->retryFunction)) {
                call_user_func($this->retryFunction, $sessions);
            } else {
                $this->sessionCounts = $sessions;
            }
        }
    }

    /**
     * @param callable $function
     *
     * @return void
     */
    public function setRetryFunction($function)
    {
        if (!is_callable($function)) {
            throw new InvalidArgumentException('The retry function must be callable');
        }

        $this->retryFunction = $function;
    }

    /**
     * @param string $minute
     *
     * @return void
     */
    protected function incrementSessions($minute)
    {
        if (array_key_exists($minute, $this->sessionCounts)) {
            $this->sessionCounts[$minute] += 1;
        } else {
            $this->sessionCounts[$minute] = 1;
        }

        if (count($this->sessionCounts) > SessionTrackerInterface::MAX_SESSION_COUNT) {
            $this->trimOldestSessions();
        }

        if ((time() - $this->lastSent) > SessionTrackerInterface::DELIVERY_INTERVAL) {
            $this->sendSessions();
        }
    }

    /**
     * @return void
     */
    protected function trimOldestSessions()
    {
        uksort($this->sessionCounts, function ($key) {
            return strtotime($key);
        });

        $sessions = array_reverse($this->sessionCounts);
        $sessionCounts = array_slice($sessions, 0, SessionTrackerInterface::MAX_SESSION_COUNT);

        $this->sessionCounts = $sessionCounts;
    }

    /**
     * @param array $sessions
     *
     * @return array
     */
    protected function constructPayload(array $sessions)
    {
        $formattedSessions = [];

        foreach ($sessions as $minute => $count) {
            $formattedSessions[] = ['startedAt' => $minute, 'sessionsStarted' => $count];
        }

        return [
            'notifier' => $this->config->getNotifier(),
            'device' => $this->config->getDeviceData(),
            'app' => $this->config->getAppData(),
            'sessionCounts' => $formattedSessions,
        ];
    }
}
