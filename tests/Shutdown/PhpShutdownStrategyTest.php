<?php

namespace Bugsnag\Tests\Shutdown;

use Bugsnag\Client;
use Bugsnag\Shutdown\PhpShutdownStrategy;
use Bugsnag\Tests\TestCase;
use Mockery;
use phpmock\spy\Spy;

/**
 * @runTestsInSeparateProcesses
 */
class PhpShutdownStrategyTest extends TestCase
{
    public function testRegisterShutdownFunction()
    {
        // Override/spy on the native PHP method when executed within the Bugsnag\Shutdown namespace
        $shutdownSpy = new Spy('Bugsnag\Shutdown', 'register_shutdown_function');
        $shutdownSpy->enable();

        // Mock a bugsnag client
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('flush');

        // Execute the shutdown strategy
        $strategy = new PhpShutdownStrategy();
        $strategy->registerShutdownStrategy($mockClient);

        // Assert that register_shutdown_function was called with [$client, "flush"]
        list($args) = $shutdownSpy->getInvocations()[0]->getArguments();
        $this->assertEquals($mockClient, $args[0]);
        $this->assertEquals('flush', $args[1]);
    }

    public function testDefaultShutdownStrategyIsCreatedWithinClientConstructor()
    {
        // Override/spy on the native PHP method when executed within the Bugsnag\Shutdown namespace
        $shutdownSpy = new Spy('Bugsnag\Shutdown', 'register_shutdown_function');
        $shutdownSpy->enable();

        $client = Client::make('api-key-here');

        // Assert that register_shutdown_function was called with [$client, "flush"]
        list($args) = $shutdownSpy->getInvocations()[0]->getArguments();
        $this->assertEquals($client, $args[0]);
        $this->assertEquals('flush', $args[1]);
    }
}
