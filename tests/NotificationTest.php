<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Error;
use Bugsnag\Middleware\AddEnvironmentData;
use Bugsnag\Notification;
use Bugsnag\Pipeline;
use Exception;
use GuzzleHttp\Client;
use phpmock\phpunit\PHPMock;

class NotificationTest extends AbstractTestCase
{
    use PHPMock;

    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Pipeline */
    protected $pipeline;
    /** @var \GuzzleHttp\Client */
    protected $guzzle;
    /** @var \Bugsnag\Notification|\PHPUnit_Framework_MockObject_MockObject */
    protected $notification;

    protected function setUp()
    {
        $this->config = new Configuration('6015a72ff14038114c3d12623dfb018f');

        $this->pipeline = (new Pipeline())->pipe(function (Error $error, callable $next) {
            if ($error->name === 'SkipMe') {
                return false;
            }

            return $next($error);
        });

        $this->guzzle = $this->getMockBuilder(Client::class)
                             ->setMethods(['request'])
                             ->getMock();

        $this->notification = new Notification($this->config, $this->pipeline, $this->guzzle);
    }

    public function testNotification()
    {
        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('request');

        // Add an error to the notification and deliver it
        $this->notification->addError($this->getError()->setMetaData(['foo' => 'bar']));
        $this->notification->deliver();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = $invocations[0]->parameters;
        $this->assertCount(3, $params);
        $this->assertSame('POST', $params[0]);
        $this->assertSame('/', $params[1]);
        $this->assertInternalType('array', $params[2]);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $params[2]['json']['apiKey']);
        $this->assertInternalType('array', $params[2]['json']['notifier']);
        $this->assertInternalType('array', $params[2]['json']['events']);
        $this->assertSame([], $params[2]['json']['events'][0]['user']);
        $this->assertSame(['foo' => 'bar'], $params[2]['json']['events'][0]['metaData']);
    }

    public function testMassiveMetaDataNotification()
    {
        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('request');

        // Add an error to the notification and deliver it
        $this->notification->addError($this->getError()->setMetaData(['foo' => str_repeat('A', 1000000)]));
        $this->notification->deliver();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = $invocations[0]->parameters;
        $this->assertCount(3, $params);
        $this->assertSame('POST', $params[0]);
        $this->assertSame('/', $params[1]);
        $this->assertInternalType('array', $params[2]);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $params[2]['json']['apiKey']);
        $this->assertInternalType('array', $params[2]['json']['notifier']);
        $this->assertInternalType('array', $params[2]['json']['events']);
        $this->assertSame([], $params[2]['json']['events'][0]['user']);
        $this->assertArrayNotHasKey('metaData', $params[2]['json']['events'][0]);
    }

    public function testMassiveUserNotification()
    {
        // Setup error_log mocking
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Payload too large'));

        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('request');

        // Add an error to the notification and deliver it
        $this->notification->addError($this->getError()->setUser(['foo' => str_repeat('A', 1000000)]));
        $this->notification->deliver();

        $this->assertCount(0, $spy->getInvocations());
    }

    public function testPartialNotification()
    {
        // Setup error_log mocking
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Payload too large'));

        // Expect request to be called
        $this->guzzle->expects($spy = $this->any())->method('request');

        // Add two errors to the notification and deliver them
        $this->notification->addError($this->getError()->setUser(['foo' => str_repeat('A', 1000000)]));
        $this->notification->addError($this->getError()->setUser(['foo' => 'bar']));
        $this->notification->deliver();

        $this->assertCount(1, $invocations = $spy->getInvocations());
        $params = $invocations[0]->parameters;
        $this->assertCount(3, $params);
        $this->assertSame('POST', $params[0]);
        $this->assertSame('/', $params[1]);
        $this->assertInternalType('array', $params[2]);
        $this->assertSame('6015a72ff14038114c3d12623dfb018f', $params[2]['json']['apiKey']);
        $this->assertInternalType('array', $params[2]['json']['notifier']);
        $this->assertInternalType('array', $params[2]['json']['events']);
        $this->assertSame(['foo' => 'bar'], $params[2]['json']['events'][0]['user']);
        $this->assertSame([], $params[2]['json']['events'][0]['metaData']);
    }

    public function testNotificationFails()
    {
        // Setup error_log mocking
        $log = $this->getFunctionMock('Bugsnag', 'error_log');
        $log->expects($this->once())->with($this->equalTo('Bugsnag Warning: Couldn\'t notify. Guzzle exception thrown!'));

        // Expect request to be called
        $this->guzzle->method('request')->will($this->throwException(new Exception('Guzzle exception thrown!')));

        // Add an error to the notification and deliver it
        $this->notification->addError($this->getError()->setMetaData(['foo' => 'bar']));
        $this->notification->deliver();
    }

    public function testBeforeNotifySkipsError()
    {
        $this->guzzle->expects($this->never())->method('request');

        $this->notification->addError($this->getError('SkipMe', 'Message'));
        $this->notification->deliver();
    }

    public function testNoEnvironmentByDefault()
    {
        $_ENV['SOMETHING'] = 'blah';

        $notification = new Notification($this->config, $this->pipeline, $this->guzzle);
        $notification->addError($this->getError());
        $notificationArray = $notification->toArray();
        $this->assertArrayNotHasKey('Environment', $notificationArray['events'][0]['metaData']);
    }

    public function testEnvironmentPresentWhenRequested()
    {
        $_ENV['SOMETHING'] = 'blah';

        $this->pipeline->pipe(new AddEnvironmentData());
        $notification = new Notification($this->config, $this->pipeline, $this->guzzle);
        $notification->addError($this->getError());
        $notificationArray = $notification->toArray();
        $this->assertSame($notificationArray['events'][0]['metaData']['Environment']['SOMETHING'], 'blah');
    }
}
