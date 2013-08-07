<?php
  require_once(__DIR__."/client.php");
  
  $bugsnag = new Bugsnag\Client("6015a72ff14038114c3d12623dfb018f");
  // $client->notifyError("Hello", "Message");

  set_error_handler(array($bugsnag, "errorHandler"));
  set_exception_handler(array($bugsnag, "exceptionHandler"));

  $this->hi;
?>