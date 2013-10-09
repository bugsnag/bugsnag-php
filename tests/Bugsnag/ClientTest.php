<?php

class ClientTest extends PHPUnit_Framework_TestCase {
    protected $client;

    public function testErrorReportingRejection() {
        $client = $this->getMockBuilder('BugsnagClient')
                       ->setMethods(array("notify"))
                       ->setConstructorArgs(array("6015a72ff14038114c3d12623dfb018f"))
                       ->getMock();

        $client->expects($this->never())
               ->method("notify");

        $client->setErrorReportingLevel(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
        $client->errorHandler(E_STRICT, "Broken!");

    }

    public function testErrorReportingAcceptance() {
        $client = $this->getMockBuilder('BugsnagClient')
                       ->setMethods(array("notify"))
                       ->setConstructorArgs(array("6015a72ff14038114c3d12623dfb018f"))
                       ->getMock();

        $client->expects($this->once())
               ->method("notify");

        $client->setErrorReportingLevel(E_ALL);
        $client->errorHandler(E_STRICT, "Broken!");
    }
}

?>