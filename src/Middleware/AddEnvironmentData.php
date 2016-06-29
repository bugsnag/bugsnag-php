<?php

namespace Bugsnag\Middleware;

use Bugsnag\Error;

class AddEnvironmentData
{
    /**
     * Execute the add environment data middleware.
     *
     * @param \Bugsnag\Error $error
     * @param callable       $next
     *
     * @return void
     */
    public function __invoke(Error $error, callable $next)
    {
        if (!empty($_ENV)) {
            $error->setMetaData(['Environment' => $_ENV]);
        }

        $next($error);
    }
}
