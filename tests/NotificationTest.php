<?php

namespace Bugsnag\Tests;

use Bugsnag\Configuration;
use Bugsnag\Diagnostics;
use Bugsnag\Notification;

class NotificationTest extends AbstractTestCase
{
    /** @var \Bugsnag\Configuration */
    protected $config;
    /** @var \Bugsnag\Diagnostics */
    protected $diagnostics;
    /** @var \Bugsnag\Notification|\PHPUnit_Framework_MockObject_MockObject */
    protected $notification;

    protected function setUp()
    {
        $this->config = new Configuration();
        $this->config->apiKey = '6015a72ff14038114c3d12623dfb018f';
        $this->config->beforeNotifyFunction = 'Bugsnag\Tests\before_notify_skip_error';

        $this->diagnostics = new Diagnostics($this->config);

        $this->notification = $this->getMockBuilder(Notification::class)
                                   ->setMethods(['postJSON'])
                                   ->setConstructorArgs([$this->config])
                                   ->getMock();
    }

    public function testNotification()
    {
        // Create a mock notification object
        $this->notification = $this->getMockBuilder(Notification::class)
                                   ->setMethods(['postJSON'])
                                   ->setConstructorArgs([$this->config])
                                   ->getMock();

        // Expect postJSON to be called
        $this->notification->expects($this->once())
                           ->method('postJSON')
                           ->with($this->equalTo('https://notify.bugsnag.com'),
                                  $this->anything());

        // Add an error to the notification and deliver it
        $this->notification->addError($this->getError());
        $this->notification->deliver();
    }

    public function testBeforeNotifySkipsError()
    {
        $this->notification->expects($this->never())
                           ->method('postJSON');

        $this->notification->addError($this->getError('SkipMe', 'Message'));
        $this->notification->deliver();
    }

    /**
     * Test for ensuring that the addError method calls shouldNotify.
     *
     * If shouldNotify returns false, the error should not be added
     */
    public function testAddErrorChecksShouldNotifyFalse()
    {
        $config = $this->getMockBuilder(Configuration::class)
                                     ->setMethods(['shouldNotify'])
                                     ->getMock();
        $config->expects($this->once())
                ->method('shouldNotify')
                ->will($this->returnValue(false));

        /** @var \Bugsnag\Notification $notification */
        $notification = $this->getMockBuilder(Notification::class)
                                     ->setMethods(['postJSON'])
                                     ->setConstructorArgs([$config])
                                     ->getMock();

        $this->assertFalse($notification->addError($this->getError()));
    }

    /**
     * Test for ensuring that the deliver method calls shouldNotify.
     *
     * If shouldNotify returns false, the error should not be sent
     */
    public function testDeliverChecksShouldNotify()
    {
        $config = $this->getMockBuilder(Configuration::class)
                                     ->setMethods(['shouldNotify'])
                                     ->getMock();
        $config->expects($this->once())
                ->method('shouldNotify')
                ->will($this->returnValue(false));

        /** @var \Bugsnag\Notification|\PHPUnit_Framework_MockObject_MockObject $notification */
        $notification = $this->getMockBuilder(Notification::class)
                                     ->setMethods(['postJSON'])
                                     ->setConstructorArgs([$config])
                                     ->getMock();

        $notification->expects($this->never())
                             ->method('postJSON');

        $notification->addError($this->getError());
        $notification->deliver();
    }

    public function testNoEnvironmentByDefault()
    {
        $_ENV['SOMETHING'] = 'blah';

        $notification = new Notification($this->config);
        $notification->addError($this->getError());
        $notificationArray = $notification->toArray();
        $this->assertArrayNotHasKey('Environment', $notificationArray['events'][0]['metaData']);
    }

    public function testEnvironmentPresentWhenRequested()
    {
        $_ENV['SOMETHING'] = 'blah';

        $this->config->sendEnvironment = true;
        $notification = new Notification($this->config);
        $notification->addError($this->getError());
        $notificationArray = $notification->toArray();
        $this->assertEquals($notificationArray['events'][0]['metaData']['Environment']['SOMETHING'], 'blah');
    }
}

function before_notify_skip_error($error)
{
    return $error->name != 'SkipMe';
}
