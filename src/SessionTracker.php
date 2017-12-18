<?php

namespace Bugsnag;

class SessionTracker
{
    /**
     * The amount of time between each sending attempt.
     */
    protected static $DELIVERY_INTERVAL = 60;

    /**
     * The maximum amount of sessions to hold onto.
     */
    protected static $MAX_SESSION_COUNT = 50;

    /**
     * The current payload version.
     */
    protected static $SESSION_PAYLOAD_VERSION = '1.0';

    /**
     * The current client configuration.
     *
     * @var Configuration
     */
    protected $config;

    /**
     * An array of session counts.
     *
     * @var array
     */
    protected $sessionCounts = [];

    /**
     * A locking function for synchronisation.
     *
     * @var function
     */
    protected $lockFunction = null;

    /**
     * An unlocking function for synchronisation.
     *
     * @var function
     */
    protected $unlockFunction = null;

    /**
     * A function to use when retrying a failed delivery.
     *
     * @var function
     */
    protected $retryFunction = null;

    /**
     * The last time the sessions were delivered.
     *
     * @var int
     */
    protected $lastSent;

    /**
     * The time between each send attempt in seconds.
     *
     * @var int
     */
    protected $sendInterval = 60;

    /**
     * Create a session tracker instance.
     *
     * @param Configuration $config the initial client configuration
     *
     * @return void
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->lastSent = 0;
    }

    public function setConfig(Configuration $config)
    {
        $this->config = $config;
    }

    public function createSession()
    {
        print_r("Creating\n");
        if (!$this->config->shouldTrackSessions()) {
            return;
        }
        $currentTime = strftime('%Y-%m-%dT%H:%M:00');
        $session = [
            'id' => uniqid('', true),
            'startedAt' => $currentTime,
            'events' => [
                'handled' => 0,
                'unhandled' => 0,
            ],
        ];
        $this->incrementSessions($currentTime);
    }

    public function sendSessions()
    {
        $locked = false;
        if (!is_null($this->lockFunction) && !is_null($this->unlockFunction)) {
            call_user_func($this->lockFunction);
            $locked = true;
        }

        try {
            $this->deliverSessions();
        } finally {
            if ($locked) {
                call_user_func($this->unlockFunction);
            }
        }
    }

    public function setLockFunctions($lock, $unlock)
    {
        if (is_callable($lock) && is_callable($unlock)) {
            $this->lockFunction = $lock;
            $this->unlockFunction = $unlock;
        } else {
            throw new InvalidArgumentException('Both lock and unlock functions must be callable');
        }
    }

    public function setRetryFunction($retry)
    {
        if (is_callable($retry)) {
            $this->retryFunction = $retry;
        } else {
            throw new InvalidArgumentException('The retry function must be callable');
        }
    }

    protected function incrementSessions($minute, $count = 1, $deliver = true)
    {
        $locked = false;
        if (!is_null($this->lockFunction) && !is_null($this->unlockFunction)) {
            call_user_func($this->lockFunction);
            $locked = true;
        }

        try {
            if (array_key_exists($minute, $this->sessionCounts)) {
                $this->sessionCounts[$minute] += $count;
            } else {
                $this->sessionCounts[$minute] = $count;
            }
            if (count($this->sessionCounts) > self::$MAX_SESSION_COUNT) {
                $this->trimOldestSessions();
            }
            if ($deliver && ((time() - $this->lastSent) > self::$DELIVERY_INTERVAL)) {
                $this->deliverSessions();
            }
        } finally {
            if ($locked) {
                call_user_func($this->unlockFunction);
            }
        }
    }

    protected function trimOldestSessions()
    {
        $sessions = $this->sessionCounts;
        uksort($sessions, function ($key) {
            return strtotime($key);
        });
        $sessions = array_reverse($sessions);
        $this->sessionCounts = array_slice($sessions, 0, self::$MAX_SESSION_COUNT);
    }

    protected function constructPayload($sessions)
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

    protected function deliverSessions()
    {
        print_r("delivering\n");
        if (!$this->config->shouldTrackSessions()) {
            return;
        }
        $sessions = $this->sessionCounts;
        $this->sessionCounts = [];
        if (count($sessions) == 0) {
            return;
        }
        $http = $this->config->getSessionClient();
        $payload = $this->constructPayload($sessions);
        $headers = [
            'Bugsnag-Api-Key' => $this->config->getApiKey(),
            'Bugsnag-Payload-Version' => self::$SESSION_PAYLOAD_VERSION,
            'Bugsnag-Sent-At' => strftime('%Y-%m-%dT%H:%M:%S'),
        ];
        $this->lastSent = time();

        try {
            $http->post('', [
                'json' => $payload,
                'headers' => $headers,
            ]);
        } catch (Exception $e) {
            error_log('Bugsnag Warning: Couldn\'t notify. '.$e->getMessage());
            if (!is_null($this->retryFunction)) {
                call_user_func($this->retryFunction, $sessions);
            } else {
                foreach ($sessions as $minute => $count) {
                    $this->incrementSessions($minute, $count, false);
                }
            }
        }
    }
}
