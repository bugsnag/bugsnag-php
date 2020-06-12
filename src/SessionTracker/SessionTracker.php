<?php

namespace Bugsnag\SessionTracker;

use Bugsnag\Configuration;
use Bugsnag\HttpClient;
use Exception;
use InvalidArgumentException;

/**
 * In a web application, a Bugsnag session refers to a single HTTP request. This
 * is not the same as the PHP session, but is named this way to align with the
 * Bugsnag session API.
 */
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
     * @var CurrentSession
     */
    protected $currentSession;

    /**
     * An array of session counts.
     *
     * This is stored with the current minute (a datetime string in the format
     * '%Y-%m-%dT%H:%M:00') as the key and the count of sessions within that
     * minute as the value.
     *
     * @var array<string, int>
     */
    protected $sessionCounts = [];

    /**
     * A function to use when retrying a failed delivery.
     *
     * If this is not given, we will not retry a failed request, but will keep
     * the sessions internally, so they can be sent with the next request. If no
     * follow up request it made, these sessions will not be sent.
     *
     * @var callable|null
     */
    protected $retryFunction = null;

    /**
     * @param Configuration  $config
     * @param HttpClient     $http
     * @param CurrentSession $currentSession
     */
    public function __construct(
        Configuration $config,
        HttpClient $http,
        CurrentSession $currentSession
    ) {
        $this->config = $config;
        $this->http = $http;
        $this->currentSession = $currentSession;
    }

    /**
     * Start a new session.
     *
     * This will create a new "current session" and increment the count of
     * sessions within this minute.
     *
     * @return void
     */
    public function startSession()
    {
        $currentTime = strftime('%Y-%m-%dT%H:%M:00');

        $this->currentSession->start($currentTime);

        if (array_key_exists($currentTime, $this->sessionCounts)) {
            $this->sessionCounts[$currentTime] += 1;
        } else {
            $this->sessionCounts[$currentTime] = 1;
        }

        if (count($this->sessionCounts) > SessionTrackerInterface::MAX_SESSION_COUNT) {
            $this->trimOldestSessions();
        }
    }

    /**
     * @return CurrentSession
     */
    public function getCurrentSession()
    {
        return $this->currentSession;
    }

    /**
     * Manually send sessions to Bugsnag.
     *
     * If no sessions have been recorded with 'startSession' or if the current
     * release stage is ignored, then nothing will be sent.
     *
     * This should not usually need to be called manually as it will be called
     * automatically by the destructor.
     *
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
     * Set a function that will be called if 'sendSessions' fails.
     *
     * This will receive the session counts as its only parameter
     * ({@see SessionTracker::$sessionCounts}) and should not return a value.
     *
     * @param callable $function
     *
     * @throws InvalidArgumentException if $function is not callable
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
     * On destruct we send any sessions that have been started. This allows us
     * to batch them into one request, rather than sending each in a separate
     * request.
     *
     * The "queue" of sessions can be manually flushed with 'sendSessions', if
     * necessary.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->sendSessions();
    }

    /**
     * @return void
     */
    protected function trimOldestSessions()
    {
        // Sort the session counts so that the oldest minutes are first
        // i.e. '2000-01-01T00:00:00' should be after '2000-01-01T00:01:00'
        uksort($this->sessionCounts, function ($a, $b) {
            return strtotime($b) - strtotime($a);
        });

        $this->sessionCounts = array_slice(
            $this->sessionCounts,
            0,
            SessionTrackerInterface::MAX_SESSION_COUNT
        );
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
