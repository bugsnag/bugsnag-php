<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Diagnostics;
use Bugsnag\Notification;
use Bugsnag\Request\BasicResolver;
use GuzzleHttp\Client;

class NotificationTest extends AbstractTestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Request\ResolverInterface */
    protected $resolver;
    /** @var \Bugsnag\Diagnostics */
    protected $diagnostics;
    /** @var \GuzzleHttp\Client */
    protected $guzzle;
    /** @var \Bugsnag\Notification|\PHPUnit_Framework_MockObject_MockObject */
    protected $notification;

    protected function setUp()
    {
        $this->config = new Configuration('6015a72ff14038114c3d12623dfb018f');
        $this->config->beforeNotifyFunction = 'Bugsnag\Tests\before_notify_skip_error';
        $this->config->user = ['id' => 'foo'];
        $this->resolver = new BasicResolver();
        $this->diagnostics = new Diagnostics($this->config, $this->resolver);

        $this->guzzle = $this->getMockBuilder(Client::class)
                             ->setMethods(['request'])
                             ->getMock();

        $this->notification = new Notification($this->config, $this->resolver, $this->guzzle);
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
        $this->assertSame(['id' => 'foo'], $params[2]['json']['events'][0]['user']);
    }

    public function testBeforeNotifySkipsError()
    {
        $this->guzzle->expects($this->never())->method('request');

        $this->notification->addError($this->getError('SkipMe', 'Message'));
        $this->notification->deliver();
    }

    /**
     * Test for ensuring that the addError method calls shouldNotify.
     *
     * If shouldNotify returns false, the error should not be added.
     */
    public function testAddErrorChecksShouldNotifyFalse()
    {
        $config = $this->getMockBuilder(Configuration::class)
                       ->setMethods(['shouldNotify'])
                       ->setConstructorArgs(['key'])
                       ->getMock();

        $config->expects($this->once())
               ->method('shouldNotify')
               ->will($this->returnValue(false));

        $notification = new Notification($config, $this->resolver, $this->guzzle);

        $this->assertFalse($notification->addError($this->getError()));
    }

    /**
     * Test for ensuring that the deliver method calls shouldNotify.
     *
     * If shouldNotify returns false, the error should not be sent.
     */
    public function testDeliverChecksShouldNotify()
    {
        $config = $this->getMockBuilder(Configuration::class)
                       ->setMethods(['shouldNotify'])
                       ->setConstructorArgs(['key'])
                       ->getMock();

        $config->expects($this->once())
               ->method('shouldNotify')
               ->will($this->returnValue(false));

        $notification = new Notification($config, $this->resolver, $this->guzzle);

        $this->guzzle->expects($this->never())->method('request');

        $notification->addError($this->getError());
        $notification->deliver();
    }

    public function testNoEnvironmentByDefault()
    {
        $_ENV['SOMETHING'] = 'blah';

        $notification = new Notification($this->config, $this->resolver, $this->guzzle);
        $notification->addError($this->getError());
        $notificationArray = $notification->toArray();
        $this->assertArrayNotHasKey('Environment', $notificationArray['events'][0]['metaData']);
    }

    public function testEnvironmentPresentWhenRequested()
    {
        $_ENV['SOMETHING'] = 'blah';

        $this->config->sendEnvironment = true;
        $notification = new Notification($this->config, $this->resolver, $this->guzzle);
        $notification->addError($this->getError());
        $notificationArray = $notification->toArray();
        $this->assertSame($notificationArray['events'][0]['metaData']['Environment']['SOMETHING'], 'blah');
    }
}

function before_notify_skip_error($error)
{
    return $error->name != 'SkipMe';
}
