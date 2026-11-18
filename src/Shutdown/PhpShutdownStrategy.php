<?php

namespace Bugsnag\Shutdown;

use Bugsnag\Client;

/**
 * Class PhpShutdownStrategy.
 *
 * Use the built-in PHP shutdown function
 */
class PhpShutdownStrategy implements ShutdownStrategyInterface
{
    /**
     * @param Client $client
     *
     * @return void
     */
    public function registerShutdownStrategy(Client $client)
    {
        // Hold the client weakly so the shutdown function does not keep it
        // (and everything it references) alive until the process exits. Keep
        // its HTTP client, which holds the queue, so no events are lost if
        // the client is freed first.
        if (!class_exists('WeakReference')) {
            register_shutdown_function([$client, 'flush']);

            return;
        }

        $reference = \WeakReference::create($client);
        $http = $client->getHttpClient();

        register_shutdown_function(static function () use ($reference, $http) {
            $client = $reference->get();

            if ($client !== null) {
                $client->flush();
            } else {
                $http->sendEvents();
            }
        });
    }
}
