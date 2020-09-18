<?php

namespace Bugsnag\Tests\Shutdown;

use Bugsnag\Client;
use Bugsnag\Shutdown\PhpShutdownStrategy;
use Bugsnag\Tests\Assert;
use Bugsnag\Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class PhpShutdownStrategyTest extends TestCase
{
    public function testShutdownFunctionIsRegisteredAsExpected()
    {
        $shutdownSpy = $this->getFunctionMock('Bugsnag\Shutdown', 'register_shutdown_function');

        // We expect to get called once by the Client constructor and once
        // manually in this test
        $shutdownSpy->expects($this->exactly(2))
            ->with(
                $this->callback(function ($callable) {
                    Assert::isType('callable', $callable);
                    Assert::isType('array', $callable);

                    $this->assertCount(2, $callable);
                    $this->assertInstanceOf(Client::class, $callable[0]);
                    $this->assertSame('flush', $callable[1]);

                    return true;
                })
            );

        $client = Client::make('api-key-here');

        $strategy = new PhpShutdownStrategy();
        $strategy->registerShutdownStrategy($client);
    }
}
