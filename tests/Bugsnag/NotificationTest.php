<?php

require_once 'Bugsnag_TestCase.php';

class NotificationTest extends Bugsnag_TestCase
{
    protected $config;
    protected $diagnostics;
    protected $notification;

    protected function setUp()
    {
        $this->config = new Bugsnag_Configuration();
        $this->config->apiKey = "6015a72ff14038114c3d12623dfb018f";
        $this->config->beforeNotifyFunction = "before_notify_skip_error";

        $this->diagnostics = new Bugsnag_Diagnostics($this->config);

        $this->notification = $this->getMockBuilder('Bugsnag_Notification')
                                   ->setMethods(array("postJSON"))
                                   ->setConstructorArgs(array($this->config))
                                   ->getMock();
    }

    public function testNotification()
    {
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
        $this->notification->addError($this->getError());
        $this->notification->deliver();
    }

    public function testBeforeNotifySkipsError()
    {
        $this->notification->expects($this->never())
                           ->method("postJSON");

        $this->notification->addError($this->getError("SkipMe","Message"));
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

        $this->assertFalse($notification->addError($this->getError()));
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

        $notification->addError($this->getError());
        $notification->deliver();
    }

    public function testUTF8()
    {
        $notification = $this->getMockBuilder('Bugsnag_Notification')
            ->disableOriginalConstructor()
            ->getMock();

        $method = new ReflectionMethod('Bugsnag_Notification', 'utf8');
        $method->setAccessible(true);

        // Test simple value, array and object
        $testObj = new \stdClass();
        $testObj->test1 = 'test2';
        $testObj->test2 = 'test3';
        $this->assertSame(
            array(
                'test1' => 'test2',
                'test3' => array('test1' => 'test2', 'test3' => json_encode($testObj)),
                'test5' => json_encode($testObj),
            ),
            $method->invoke($notification, array(
                'test1' => 'test2',
                'test3' => array('test1' => 'test2', 'test3' => $testObj),
                'test5' => $testObj
            ))
        );
    }
}

function before_notify_skip_error($error)
{
    return $error->name != "SkipMe";
}
