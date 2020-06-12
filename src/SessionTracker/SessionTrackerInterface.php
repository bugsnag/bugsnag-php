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
