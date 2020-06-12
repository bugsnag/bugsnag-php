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
     * @param callable $function
     *
     * @return void
     */
    public function setRetryFunction($function);
}
