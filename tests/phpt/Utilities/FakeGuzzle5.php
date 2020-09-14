<?php

namespace Bugsnag\Tests\phpt\Utilities;

use BadMethodCallException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\Response;

/**
 * A Guzzle 5 compatible implementation of ClientInterface for use in PHPT tests.
 *
 * This should never be used directly; use 'FakeGuzzle' instead!
 */
class FakeGuzzle5 implements ClientInterface
{
    public function post($url = null, array $options = [])
    {
        reportRequest('POST', $url, $options);

        return new Response(200);
    }

    public function createRequest($method, $url = null, array $options = [])
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function get($url = null, $options = [])
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function head($url = null, array $options = [])
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function delete($url = null, array $options = [])
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function put($url = null, array $options = [])
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function patch($url = null, array $options = [])
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function options($url = null, array $options = [])
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function send(RequestInterface $request)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function getDefaultOption($keyOrPath = null)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function setDefaultOption($keyOrPath, $value)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function getBaseUrl()
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function getEmitter()
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }
}
