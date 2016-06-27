<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Diagnostics;
use Bugsnag\Error;
use Bugsnag\Middleware\AddEnvironmentData;
use Bugsnag\Notification;
use Bugsnag\Pipeline;
use Bugsnag\Request\BasicResolver;
use GuzzleHttp\Client;

class NotificationTest extends AbstractTestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Pipeline */
    protected $pipeline;
    /** @var \Bugsnag\Diagnostics */
    protected $diagnostics;
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

        $this->diagnostics = new Diagnostics($this->config, new BasicResolver());

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
        $this->notification->addError($this->getError());
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
