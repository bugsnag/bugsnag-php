<?php

namespace Bugsnag\Shutdown;

use Bugsnag\Client;

/**
 * Interface ShutdownStrategyInterface.
 */
interface ShutdownStrategyInterface
{
    /**
     * Register the shutdown behaviour.
     *
     * @param Client $client
     */
    public function register(Client $client);
}
