<?php

class NotificationTest extends PHPUnit_Framework_TestCase {
    protected $config;
    protected $notification;

    protected function setUp(){
        $this->config = new Bugsnag_Configuration();
        $this->config->apiKey = "6015a72ff14038114c3d12623dfb018f";
        $this->config->beforeNotifyFunction = "before_notify_skip_error";

        $this->notification = $this->getMockBuilder('Bugsnag_Notification')
                                   ->setMethods(array("postJSON"))
                                   ->setConstructorArgs(array($this->config))
                                   ->getMock();
    }

    public function testNotification() {
        // Create a mock notification object
        $this->notification = $this->getMockBuilder('Bugsnag_Notification')
                                   ->setMethods(array("postJSON"))
                                   ->setConstructorArgs(array($this->config))
                                   ->getMock();

        // Expect postJSON to be called
        $this->notification->expects($this->once())
                           ->method("postJSON")
                           ->with($this->equalTo("https://notify.bugsnag.com"),
                                  $this->anything());

        // Add an error to the notification and deliver it
        $this->notification->addError(Bugsnag_Error::fromNamedError($this->config, "Name", "Message"));
        $this->notification->deliver();
    }

    public function testBeforeNotifySkipsError() {
        $this->notification->expects($this->never())
                           ->method("postJSON");

        $this->notification->addError(Bugsnag_Error::fromNamedError($this->config, "SkipMe", "Message"));
        $this->notification->deliver();
    }

    /**
     * Test for ensuring that the addError method calls shouldNotify
     *
     * If shouldNotify returns false, the error should not be added
     */
    public function testAddErrorChecksShouldNotifyFalse()
    {
        $config = $this->getMockBuilder('Bugsnag_Configuration')
                                     ->setMethods(array("shouldNotify"))
                                     ->getMock();
        $config->expects($this->once())
                ->method('shouldNotify')
                ->will($this->returnValue(false));

        $notification = $this->getMockBuilder('Bugsnag_Notification')
                                     ->setMethods(array("postJSON"))
                                     ->setConstructorArgs(array($config))
                                     ->getMock();

        $this->assertFalse($notification->addError(Bugsnag_Error::fromNamedError($config, "Name", "Message")));
    }

    /**
     * Test for ensuring that the deliver method calls shouldNotify
     *
     * If shouldNotify returns false, the error should not be sent
     */
    public function testDeliverChecksShouldNotify()
    {
        $config = $this->getMockBuilder('Bugsnag_Configuration')
                                     ->setMethods(array("shouldNotify"))
                                     ->getMock();
        $config->expects($this->once())
                ->method('shouldNotify')
                ->will($this->returnValue(false));

        $notification = $this->getMockBuilder('Bugsnag_Notification')
                                     ->setMethods(array("postJSON"))
                                     ->setConstructorArgs(array($config))
                                     ->getMock();

        $notification->expects($this->never())
                             ->method("postJSON");

        $notification->addError(Bugsnag_Error::fromNamedError($config, "Name", "Message"));
        $notification->deliver();
    }
}

function before_notify_skip_error($error) {
    return $error->name != "SkipMe";
}

?>