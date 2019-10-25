<?php

namespace Bugsnag\Tests\Shutdown;

use Bugsnag\Shutdown\PhpShutdownStrategy;
use PHPUnit_Framework_TestCase as TestCase;
use Bugsnag\Client;
use phpmock\spy\Spy;

class PhpShutdownStrategyTest extends TestCase
{
    public function testRegisterShutdownFunction()
    {
        // Override/spy on the native PHP method when executed within the Bugsnag\Shutdown namespace
        $shutdownSpy = new Spy( "Bugsnag\Shutdown", "register_shutdown_function");
        $shutdownSpy->enable();

        // Mock a bugsnag client
        $mockClient = $this->createMock(Client::class);

        // Execute the shutdown strategy
        $strategy = new PhpShutdownStrategy();
        $strategy->register($mockClient);

        // Assert that register_shutdown_function was called with [$client, "flush"]
        list($args) = $shutdownSpy->getInvocations()[0]->getArguments();
        $this->assertEquals($mockClient, $args[0]);
        $this->assertEquals('flush', $args[1]);
    }

}
