<?php

namespace Bugsnag\Middleware;

use Bugsnag\Report;
use Bugsnag\SessionTracker\SessionTracker;

class SessionData
{
    /**
     * @var SessionTracker
     */
    protected $sessionTracker;

    /**
     * @param SessionTracker $sessionTracker
     */
    public function __construct(SessionTracker $sessionTracker)
    {
        $this->sessionTracker = $sessionTracker;
    }

    /**
     * Attaches session information to the Report, if the SessionTracker has a
     * current session. Note that this is not the same as the PHP session, but
     * refers to the current request.
     *
     * If the SessionTracker does not have a current session, the report will
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

        if ($session->isActive()) {
            $session->handle($report);
            $report->setSessionData($session->toArray());
        }

        $next($report);
    }
}
