<?php

namespace Bugsnag\Pipeline\Middleware;

use Bugsnag\Error;

class AddEnvironmentData
{
    /**
     * Execute the add environment data middleware.
     *
     * @param \Bugsnag\Error $error
     * @param callable       $next
     *
     * @return bool
     */
    public function __invoke(Error $error, callable $next)
    {
        if ($_ENV) {
            $error->setMetaData(['Environment' => $_ENV]);
        }

        return $next($error);
    }
}
