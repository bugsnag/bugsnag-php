<?php

require_once 'vendor/autoload.php';

# Create the bugsnag client
$bugsnag = Bugsnag\Client::make();

# Stores a breadcrumb, which will be recorded and reported with any subsequent notifications
$bugsnag->leaveBreadcrumb("This is a breadcrumb");

# Send a notification to the Bugsnag dashboard with a breadcrumb included
$bugsnag->notifyException(new RuntimeException("Oh no, something went wrong"));