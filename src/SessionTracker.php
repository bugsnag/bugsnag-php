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
     * A function to store data in.
     *
     * @var function
     */
    protected $storeFunction = null;

    /**
     * A function to use to retreive data from.
     *
     * @var function
     */
    protected $retrieveFunction = null;

    /**
     * The last time the sessions were delivered.
     *
     * @var int
     */
    protected $lastSent;

    /**
     * The current session.
     *
     * @var array
     */
    protected $currentSession;

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
        $this->setCurrentSession($session);
        $this->incrementSessions($currentTime);
    }

    protected function setCurrentSession(array $session)
    {
        if (!is_null($this->storeFunction) && !is_null($this->retrieveFunction)) {
            call_user_func($this->storeFunction, 'bugsnag-current-session', $session);
        } else {
            $this->currentSession = $session;
        }
    }

    public function getCurrentSession()
    {
        if (!is_null($this->storeFunction) && !is_null($this->retrieveFunction)) {
            return call_user_func($this->retrieveFunction, 'bugsnag-current-session');
        } else {
            return $this->currentSession;
        }
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

    public function setStorageFunctions($store, $retrieve)
    {
        if (is_callable($store) && is_callable($retrieve)) {
            $this->storeFunction = $store;
            $this->retrieveFunction = $retrieve;
        } else {
            throw new InvalidArgumentException('Both store and retrieve functions must be callable');
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
            $sessionCounts = $this->getSessionCounts();

            if (array_key_exists($minute, $sessionCounts)) {
                $sessionCounts[$minute] += $count;
            } else {
                $sessionCounts[$minute] = $count;
            }

            $this->setSessionCounts($sessionCounts);

            if (count($sessionCounts) > self::$MAX_SESSION_COUNT) {
                $this->trimOldestSessions();
            }
            $lastSent = $this->getLastSent();
            if ($deliver && ((time() - $lastSent) > self::$DELIVERY_INTERVAL)) {
                $this->deliverSessions();
            }
        } finally {
            if ($locked) {
                call_user_func($this->unlockFunction);
            }
        }
    }

    protected function getSessionCounts()
    {
        if (!is_null($this->storeFunction) && !is_null($this->retrieveFunction)) {
            return call_user_func($this->retrieveFunction, 'bugsnag-session-counts');
        } else {
            return $this->sessionCounts;
        }
    }

    protected function setSessionCounts(array $sessionCounts)
    {
        if (!is_null($this->storeFunction) && !is_null($this->retrieveFunction)) {
            return call_user_func($this->storeFunction, 'bugsnag-session-counts', $sessionCounts);
        } else {
            $this->sessionCounts = $sessionCounts;
        }
    }

    protected function trimOldestSessions()
    {
        $sessions = $this->getSessionCounts();
        uksort($sessions, function ($key) {
            return strtotime($key);
        });
        $sessions = array_reverse($sessions);
        $sessionCounts = array_slice($sessions, 0, self::$MAX_SESSION_COUNT);
        $this->setSessionCounts($sessionCounts);
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
        if (!$this->config->shouldTrackSessions()) {
            return;
        }
        $sessions = $this->getSessionCounts();
        $this->setSessionCounts([]);
        print_r("Sending sessions");
        if (count($sessions) == 0) {
            print_r("No sessions to send");
            return;
        }
        $http = $this->config->getSessionClient();
        $payload = $this->constructPayload($sessions);
        $headers = [
            'Bugsnag-Api-Key' => $this->config->getApiKey(),
            'Bugsnag-Payload-Version' => self::$SESSION_PAYLOAD_VERSION,
            'Bugsnag-Sent-At' => strftime('%Y-%m-%dT%H:%M:%S'),
        ];
        $this->setLastSent();

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

    protected function setLastSent()
    {
        $time = time();
        if (!is_null($this->storeFunction) && !is_null($this->retrieveFunction)) {
            call_user_func($this->storeFunction, 'bugsnag-session-last-sent', $time);
        } else {
            $this->lastSent = $time;
        }
    }

    protected function getLastSent()
    {
        if (!is_null($this->storeFunction) && !is_null($this->retrieveFunction)) {
            return call_user_func($this->retrieveFunction, 'bugsnag-session-last-sent');
        } else {
            return $this->lastSent;
        }
    }
}
