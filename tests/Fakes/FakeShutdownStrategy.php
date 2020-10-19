<?php

namespace Bugsnag\Tests\Fakes;

use Bugsnag\Client;
use Bugsnag\Shutdown\ShutdownStrategyInterface;

final class FakeShutdownStrategy implements ShutdownStrategyInterface
{
    private $wasRegistered = false;

    public function registerShutdownStrategy(Client $client)
    {
        $this->wasRegistered = true;
    }

    public function wasRegistered()
    {
        return $this->wasRegistered;
    }
}
