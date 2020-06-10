<?php

namespace Bugsnag\SessionTracker;

interface SessionTrackerInterface
{
    /**
     * The amount of time between each sending attempt.
     */
    const DELIVERY_INTERVAL = 30;

    /**
     * The maximum amount of sessions to hold onto.
     */
    const MAX_SESSION_COUNT = 50;

    /**
     * The key for storing session counts.
     */
    const SESSION_COUNTS_KEY = 'bugsnag-session-counts';

    /**
     * The key for storing last sent data.
     */
    const SESSIONS_LAST_SENT_KEY = 'bugsnag-sessions-last-sent';

    /**
     * @return void
     */
    public function startSession();

    /**
     * @param array $session
     *
     * @return void
     */
    public function setCurrentSession(array $session);

    /**
     * @return array
     */
    public function getCurrentSession();

    /**
     * @return void
     */
    public function sendSessions();

    /**
     * @param callable $lock
     * @param callable $unlock
     *
     * @return void
     */
    public function setLockFunctions($lock, $unlock);

    /**
     * @param callable $function
     *
     * @return void
     */
    public function setRetryFunction($function);

    /**
     * @param callable $function
     *
     * @return void
     */
    public function setStorageFunction($function);

    /**
     * @param callable $function
     *
     * @return void
     */
    public function setSessionFunction($function);
}
