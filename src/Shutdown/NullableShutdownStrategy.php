<?php

namespace Bugsnag\Shutdown;

use Bugsnag\Client;

/**
 * Class NullableShutdownStrategy.
 */
class NullableShutdownStrategy implements ShutdownStrategyInterface
{
    /**
     * @param Client $client
     *
     * @return void
     */
    public function registerShutdownStrategy(Client $client)
    {
        // ...
    }
}
