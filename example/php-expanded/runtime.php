<?php

require_once 'vendor/autoload.php';

// Create a global Bugsnag client
return Bugsnag\Client::make('YOUR-API-KEY-HERE');
