<?php

namespace Bugsnag\Tests\Shutdown;

use Bugsnag\Client;
use Bugsnag\Configuration;
use Bugsnag\Report;
use Bugsnag\Shutdown\PhpShutdownStrategy;
use Bugsnag\Tests\Assert;
use Bugsnag\Tests\TestCase;
use GuzzleHttp\Client as Guzzle;

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

                    if (class_exists('WeakReference')) {
                        $this->assertInstanceOf(\Closure::class, $callable);
                    } else {
                        Assert::isType('array', $callable);
                        $this->assertCount(2, $callable);
                        $this->assertInstanceOf(Client::class, $callable[0]);
                        $this->assertSame('flush', $callable[1]);
                    }

                    return true;
                })
            );

        $client = Client::make('api-key-here');

        $strategy = new PhpShutdownStrategy();
        $strategy->registerShutdownStrategy($client);
    }

    public function testShutdownFunctionFlushesLiveClient()
    {
        $callbacks = $this->captureShutdownFunctions();

        $config = new Configuration('api-key-here');
        $guzzle = $this->getMockBuilder(Guzzle::class)
            ->setMethods([self::getGuzzleMethod()])
            ->getMock();

        $guzzle->expects($this->once())->method(self::getGuzzleMethod());

        $client = new Client($config, null, $guzzle);
        $client->notify(Report::fromNamedError($config, 'Name'));

        $this->assertCount(1, $callbacks);
        call_user_func($callbacks[0]);
    }

    public function testShutdownFunctionDoesNotKeepClientAlive()
    {
        if (!class_exists('WeakReference')) {
            $this->markTestSkipped('WeakReference requires PHP 7.4+');
        }

        $callbacks = $this->captureShutdownFunctions();

        $client = Client::make('api-key-here');
        $reference = \WeakReference::create($client);

        unset($client);
        gc_collect_cycles();

        $this->assertNull($reference->get());

        // The callback must not fail once the client is gone
        $this->assertCount(1, $callbacks);
        call_user_func($callbacks[0]);
    }

    public function testShutdownFunctionSendsQueuedEventsOfFreedClient()
    {
        if (!class_exists('WeakReference')) {
            $this->markTestSkipped('WeakReference requires PHP 7.4+');
        }

        $callbacks = $this->captureShutdownFunctions();

        $config = new Configuration('api-key-here');
        $guzzle = $this->getMockBuilder(Guzzle::class)
            ->setMethods([self::getGuzzleMethod()])
            ->getMock();

        $guzzle->expects($this->once())->method(self::getGuzzleMethod());

        $client = new Client($config, null, $guzzle);
        $client->notify(Report::fromNamedError($config, 'Name'));
        $reference = \WeakReference::create($client);

        unset($client);
        gc_collect_cycles();

        $this->assertNull($reference->get());

        $this->assertCount(1, $callbacks);
        call_user_func($callbacks[0]);
    }

    /**
     * @return \ArrayObject
     */
    private function captureShutdownFunctions()
    {
        $callbacks = new \ArrayObject();

        $shutdownSpy = $this->getFunctionMock('Bugsnag\Shutdown', 'register_shutdown_function');
        $shutdownSpy->expects($this->any())
            ->willReturnCallback(function ($callable) use ($callbacks) {
                $callbacks[] = $callable;
            });

        return $callbacks;
    }
}
