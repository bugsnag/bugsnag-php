<?php

namespace Bugsnag\Middleware;

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
     * @return void
     */
    public function __invoke(Error $error, callable $next)
    {
        if (!$this->config->shouldNotify()) {
            return;
        }

        $next($error);
    }
}
