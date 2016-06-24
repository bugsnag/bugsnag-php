<?php

namespace Bugsnag\Pipeline\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Error;

class NotificationSkipper
{
    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    protected $config;

    /**
     * Create a new notification skipper middleware instance.
     *
     * @param \Bugsnag\Configuration $config the configuration instance
     *
     * @return void
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }
    /**
     * Execute the notification skipper middleware.
     *
     * @param \Bugsnag\Error $error
     * @param callable       $next
     *
     * @return bool
     */
    public function __invoke(Error $error, callable $next)
    {
        if (!$this->shouldNotify()) {
            return false;
        }

        return $next($error);
    }

    /**
     * Should we notify?
     *
     * @return bool
     */
    protected function shouldNotify()
    {
        if (is_null($this->config->notifyReleaseStages)) {
            return true;
        }

        return is_array($this->config->notifyReleaseStages) && in_array($this->config->releaseStage, $this->config->notifyReleaseStages, true);
    }
}
