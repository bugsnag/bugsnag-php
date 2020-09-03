<?php

namespace Bugsnag\Tests\phpt\Utilities;

use BadMethodCallException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * A Guzzle 6 compatible implementation of ClientInterface for use in PHPT tests.
 *
 * This should never be used directly; use 'FakeGuzzle' instead!
 */
class FakeGuzzle6 implements ClientInterface
{
    public function request($method, $uri, array $options = [])
    {
        reportRequest($method, $uri, $options);

        return new Response();
    }

    public function send(RequestInterface $request, array $options = [])
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function sendAsync(RequestInterface $request, array $options = [])
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function requestAsync($method, $uri, array $options = [])
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function getConfig($option = null)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }
}
