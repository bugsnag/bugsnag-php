<?php

namespace Bugsnag\Callbacks;

use Bugsnag\Error;

class EnvironmentData
{
    /**
     * Execute the environment data callback.
     *
     * @param \Bugsnag\Error $error
     *
     * @return void
     */
    public function __invoke(Error $error)
    {
        if (!empty($_ENV)) {
            $error->setMetaData(['Environment' => $_ENV]);
        }
    }
}
