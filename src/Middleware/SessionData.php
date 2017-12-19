<?php

namespace Bugsnag\Middleware;

use Bugsnag\Client;
use Bugsnag\Report;

class SessionData
{
    /**
     * The client instance.
     *
     * @var \Bugsnag\Client
     */
    protected $client;

    /**
     * Create a new session data middleware instance.
     *
     * @param \Bugsnag\SessionTracker $client the client instance.
     *
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Execute the session data middleware.
     *
     * @param \Bugsnag\Report $report the bugsnag report instance
     * @param callable        $next   the next stage callback
     *
     * @return void
     */
    public function __invoke(Report $report, callable $next)
    {
        if ($this->client->shouldTrackSessions()) {
            $session = $this->client->getSessionTracker()->getCurrentSession();
            if ($report->getUnhandled()) {
                $session['events']['unhandled'] += 1;
            } else {
                $session['events']['handled'] += 1;
            }
            $report->setSessionData($session);
        }

        $next($report);
    }
}
