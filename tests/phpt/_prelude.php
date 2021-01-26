<?php

require __DIR__.'/../../vendor/autoload.php';

use Bugsnag\Client;
use Bugsnag\Configuration;
use Bugsnag\Tests\phpt\Utilities\FakeGuzzle;

$config = new Configuration('11111111111111111111111111111111');
$config->setNotifyEndpoint('http://localhost/notify');
$config->setSessionEndpoint('http://localhost/sessions');

$guzzle = new FakeGuzzle();

$client = new Client(
    $config,
    null,
    $guzzle
);

$client->registerDefaultCallbacks();

return $client;
