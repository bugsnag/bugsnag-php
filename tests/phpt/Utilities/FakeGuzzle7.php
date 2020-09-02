<?php

namespace Bugsnag\Tests\phpt\Utilities;

use BadMethodCallException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A Guzzle 7 compatible implementation of ClientInterface for use in PHPT tests.
 *
 * This should never be used directly; use 'FakeGuzzle' instead!
 */
class FakeGuzzle7 implements ClientInterface
{
    public function request($method, $uri, array $options = []): ResponseInterface
    {
        reportRequest($method, $uri, $options);

        return new Response();
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function requestAsync($method, $uri, array $options = []): PromiseInterface
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }

    public function getConfig(?string $option = null)
    {
        throw new BadMethodCallException(__METHOD__.' is not implemented!');
    }
}
