<?php

namespace Bugsnag\SessionTracker;

interface SessionTrackerInterface
{
    /**
     * The maximum number of sessions to hold onto.
     */
    const MAX_SESSION_COUNT = 50;

    /**
     * @return void
     */
    public function startSession();

    /**
     * @return CurrentSession
     */
    public function getCurrentSession();

    /**
     * @return void
     */
    public function sendSessions();

    /**
     * @param callable $function
     *
     * @return void
     */
    public function setRetryFunction($function);
}
