<?php

namespace Bugsnag\Tests;

use Bugsnag\Client;
use Bugsnag\Configuration;
use Exception;
use GuzzleHttp\Psr7\Uri;
use PHPUnit_Framework_TestCase as TestCase;

class ClientTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Bugsnag\Client */
    protected $client;

    protected function setUp()
    {
        // Mock the notify function
        $this->client = $this->getMockBuilder(Client::class)
                             ->setMethods(['notify'])
                             ->setConstructorArgs([new Configuration('example-api-key')])
                             ->getMock();
    }

    public function testManualErrorNotification()
    {
        $this->client->expects($this->once())->method('notify');

        $this->client->notifyError('SomeError', 'Some message');
    }

    public function testManualExceptionNotification()
    {
        $this->client->expects($this->once())->method('notify');

        $this->client->notifyException(new Exception('Something broke'));
    }

    public function testDefaultSetup()
    {
        $this->assertEquals(new Uri('https://notify.bugsnag.com'), $this->client->getGuzzle()->getConfig('base_uri'));
    }

    public function testCanMake()
    {
        $client = Client::make('123', 'https://example.com');

        $this->assertInstanceOf(Client::class, $client);

        $this->assertEquals(new Uri('https://example.com'), $client->getGuzzle()->getConfig('base_uri'));
    }
}
