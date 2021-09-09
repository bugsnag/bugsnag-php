<?php

namespace Bugsnag\Tests\phpt\Utilities;

/**
 * This file creates a 'FakeGuzzle' class, which is actually an alias of a
 * different class to allow us to be compatible with the Guzzle ClientInterface
 * across major versions.
 *
 * The implementations should never be used directly, instead always refer to
 * 'FakeGuzzle'. Otherwise tests will only work on one Guzzle version
 */

use GuzzleHttp\ClientInterface;
use RuntimeException;

$fakeGuzzleMapping = [
    5 => FakeGuzzle5::class,
    6 => FakeGuzzle6::class,
    7 => FakeGuzzle7::class,
];

// Parse a version number like '1.0.0' into the major version only (1)
$parseFullVersionNumber = function ($version) {
    return (int) explode('.', $version, 1)[0];
};

$guzzleMajorVersion = null;

if (defined(ClientInterface::class.'::MAJOR_VERSION')) {
    // Guzzle 7 defines 'MAJOR_VERSION' (as an integer)
    $guzzleMajorVersion = constant(ClientInterface::class.'::MAJOR_VERSION');
} elseif (defined(ClientInterface::class.'::VERSION')) {
    // Guzzle 5 & 6 define 'VERSION', e.g. '5.0.0'
    $version = constant(ClientInterface::class.'::VERSION');

    $guzzleMajorVersion = $parseFullVersionNumber($version);
}

if ($guzzleMajorVersion === null) {
    throw new RuntimeException('Unable to determine Guzzle major version!');
}

if (!isset($fakeGuzzleMapping[$guzzleMajorVersion])) {
    throw new RuntimeException(sprintf(
        "Unsupported Guzzle major version '%s'. Supported versions are: %s",
        $guzzleMajorVersion,
        implode(', ', array_keys($fakeGuzzleMapping))
    ));
}

function reportRequest($method, $uri, $options)
{
    $numberOfEvents = 0;
    $errors = [];

    if (isset($options['body'])) {
        $payload = json_decode($options['body'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Unable to decode JSON body: '.json_last_error_msg());
        }

        $numberOfEvents = count($payload['events']);
        $errors = array_map(
            function ($event) {
                return strtok($event['exceptions'][0]['message'], "\n");
            },
            $payload['events']
        );
    }

    $errors = implode("\n    - ", $errors);
    $events = $numberOfEvents === 1 ? 'event' : 'events';

    echo "Guzzle request made ({$numberOfEvents} {$events})!\n";
    echo "* Method: '{$method}'\n";
    echo "* URI: '{$uri}'\n";
    echo "* Events:\n";
    echo "    - {$errors}\n";
}

// Create the 'FakeGuzzle' class as an alias of the correct implementation,
// based on the installed Guzzle version
class_alias($fakeGuzzleMapping[$guzzleMajorVersion], FakeGuzzle::class, true);
