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
     * @param \Bugsnag\Client $client
     *
     * @return void
     */
    public function registerShutdownStrategy(Client $client);
}
