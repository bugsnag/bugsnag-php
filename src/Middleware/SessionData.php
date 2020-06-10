<?php

namespace Bugsnag\Middleware;

use Bugsnag\Report;
use Bugsnag\SessionTracker\SessionTrackerInterface;

class SessionData
{
    /**
     * @var SessionTrackerInterface
     */
    protected $sessionTracker;

    /**
     * @param SessionTrackerInterface $sessionTracker
     */
    public function __construct(SessionTrackerInterface $sessionTracker)
    {
        $this->sessionTracker = $sessionTracker;
    }

    /**
     * Attaches session information to the Report, if the session tracker has a
     * current session. Note that this is not the same as the PHP session, but
     * refers to the current request.
     *
     * If the session tracker does not have a current session, the report will
     * not be changed.
     *
     * @param Report   $report
     * @param callable $next
     *
     * @return void
     */
    public function __invoke(Report $report, callable $next)
    {
        $session = $this->sessionTracker->getCurrentSession();

        if (isset($session['events'])) {
            if ($report->getUnhandled()) {
                $session['events']['unhandled'] += 1;
            } else {
                $session['events']['handled'] += 1;
            }

            $report->setSessionData($session);
            $this->sessionTracker->setCurrentSession($session);
        }

        $next($report);
    }
}
