<?php

namespace Bugsnag\SessionTracker;

use InvalidArgumentException;

interface SessionTrackerInterface
{
    /**
     * The maximum number of sessions to hold onto.
     *
     * If we accumulate more than this, the oldest sessions will be dropped.
     */
    const MAX_SESSION_COUNT = 50;

    /**
     * Start a new session.
     *
     * If automatic session tracking is enabled, this will be called automatically
     * when the Bugsnag Client is created. It can be called manually if automatic
     * session tracking is disabled, or if you application can have multiple
     * sessions in a single request.
     *
     * @return void
     */
    public function startSession();

    /**
     * @return CurrentSession
     */
    public function getCurrentSession();

    /**
     * Manually send sessions to Bugsnag.
     *
     * If no sessions have been recorded with 'startSession' or if the current
     * release stage is ignored, then nothing will be sent.
     *
     * This should not usually need to be called manually.
     *
     * @return void
     */
    public function sendSessions();

    /**
     * Set a function that will be called if 'sendSessions' fails.
     *
     * This will receive the session counts as its only parameter
     * ({@see SessionTracker::$sessionCounts}) and should not return a value.
     *
     * @param callable $function
     *
     * @return void
     *
     * @throws InvalidArgumentException if $function is not callable
     */
    public function setRetryFunction($function);
}
