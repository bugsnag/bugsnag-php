<?php

namespace Bugsnag\SessionTracker;

final class NullSessionTracker implements SessionTrackerInterface
{
    /**
     * @return void
     */
    public function startSession()
    {
        $this->warn(__FUNCTION__);
    }

    /**
     * @param array $session
     *
     * @return void
     */
    public function setCurrentSession(array $session)
    {
    }

    /**
     * @return array
     */
    public function getCurrentSession()
    {
        return [];
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
