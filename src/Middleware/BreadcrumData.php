<?php

namespace Bugsnag\Middleware;

use Bugsnag\Breadcrums\Recorder;
use Bugsnag\Report;

class BreadrumData
{
    /**
     * The recorder instance.
     *
     * @var \Bugsnag\Breadcrums\Recorder
     */
    protected $recorder;

    /**
     * Create a new breadcrum data middleware instance.
     *
     * @param \Bugsnag\Breadcrums\Recorder $recorder the recorder instance
     *
     * @return void
     */
    public function __construct(Recorder $recorder)
    {
        $this->recorder = $recorder;
    }

    /**
     * Execute the notification skipper middleware.
     *
     * @param \Bugsnag\Report $report the bugsnag report instance
     * @param callable        $next   the next stage callback
     *
     * @return void
     */
    public function __invoke(Report $report, callable $next)
    {
        foreach ($this->recorder as $breadcrum) {
            $report->addBreadcrum($breadcrum);
        }

        $next($report);
    }
}
