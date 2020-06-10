<?php

namespace Bugsnag\SessionTracker;

final class NullSessionTracker implements SessionTrackerInterface
{
    /**
     * @var array
     */
    private $currentSession = [];

    /**
     * @return void
     */
    public function startSession()
    {
        // TODO this is duplicated in SessionTracker
        $this->currentSession = [
            'id' => uniqid('', true),
            'startedAt' => strftime('%Y-%m-%dT%H:%M:00'),
            'events' => [
                'handled' => 0,
                'unhandled' => 0,
            ],
        ];
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
        $this->warn(__FUNCTION__);
    }

    /**
     * @param callable $lock
     * @param callable $unlock
     *
     * @return void
     */
    public function setLockFunctions($lock, $unlock)
    {
    }

    /**
     * @param callable $function
     *
     * @return void
     */
    public function setRetryFunction($function)
    {
    }

    /**
     * @param callable $function
     *
     * @return void
     */
    public function setStorageFunction($function)
    {
    }

    /**
     * @param callable $function
     *
     * @return void
     */
    public function setSessionFunction($function)
    {
    }

    private function warn($method)
    {
        error_log(
            "Bugsnag: '{$method}' cannot be called when session tracking is disabled"
        );
    }
}
