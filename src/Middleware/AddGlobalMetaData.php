<?php

namespace Bugsnag\Middleware;

use Bugsnag\Configuration;
use Bugsnag\Error;

class AddGlobalMetaData
{
    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    protected $config;

    /**
     * Create a new add global meta data middleware instance.
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
     * Execute the add global meta data middleware.
     *
     * @param \Bugsnag\Error $error
     * @param callable       $next
     *
     * @return bool
     */
    public function __invoke(Error $error, callable $next)
    {
        if ($data = $this->config->metaData) {
            $error->setMetaData($data);
        }

        return $next($error);
    }
}
