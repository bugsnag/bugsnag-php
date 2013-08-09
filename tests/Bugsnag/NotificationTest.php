<?php

class NotificationTest extends PHPUnit_Framework_TestCase {
    public function testNotification() {
        // Configuration
        $config = new Bugsnag\Configuration();
        $config->apiKey = "6015a72ff14038114c3d12623dfb018f";

        // Create a mock notification object
        $notification = $this->getMock('Bugsnag\Notification', array("postJSON"), array($config));

        // Expect postJSON to be called
        $notification->expects($this->once())
                     ->method("postJSON");

        // Add an error to the notification and deliver it
        $notification->addError(Bugsnag\Error::fromNamedError($config, "Name", "Message"));
        $notification->deliver();
    }
}

?>