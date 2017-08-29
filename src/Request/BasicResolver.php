<?php

namespace Bugsnag\Request;

class BasicResolver implements ResolverInterface
{
    /**
     * Resolve the current request.
     *
     * @return \Bugsnag\Request\RequestInterface
     */
    public function resolve()
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            return new PhpRequest($_SERVER,
                empty($_SESSION) ? [] : $_SESSION,
                empty($_COOKIE) ? [] : $_COOKIE,
                static::getRequestHeaders($_SERVER),
                static::getInputParams($_SERVER, $_POST));
        }

        if (PHP_SAPI === 'cli' && isset($_SERVER['argv'])) {
            return new ConsoleRequest($_SERVER['argv']);
        }

        return new NullRequest();
    }

    /**
     * Get the request headers.
     *
     * Note how we're caching this result for ever, across all instances.
     *
     * This is because PHP is natively only designed to process one request,
     * then shutdown. Some applications can be designed to handle multiple
     * requests using their own request objects, thus will need to implement
     * their own bugsnag request resolver.
     *
     * @param array $server the server variables
     *
     * @return array
     */
    protected static function getRequestHeaders(array $server)
    {
        static $headers;

        if ($headers !== null) {
            return $headers;
        }

        $headers = [];

        foreach ($server as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }

    /**
     * Get the input params.
     *
     * Note how we're caching this result for ever, across all instances.
     *
     * This is because the input stream can only be read once on PHP 5.5, and
     * PHP is natively only designed to process one request, then shutdown.
     * Some applications can be designed to handle multiple requests using
     * their own request objects, thus will need to implement their own bugsnag
     * request resolver.
     *
     * @param array $server the server variables
     * @param array $post   the post variables
     *
     * @return array|null
     */
    protected static function getInputParams(array $server, array $post)
    {
        static $result;

        if ($result !== null) {
            return $result ?: null;
        }

        $result = $post ?: static::parseInput($server, static::readInput());

        return $result ?: null;
    }

    /**
     * Read the PHP input stream.
     *
     * @return string|false
     */
    protected static function readInput()
    {
        return file_get_contents('php://input') ?: false;
    }

    /**
     * Parse the given input string.
     *
     * @param array       $server the server variables
     * @param string|null $input  the http request input
     *
     * @return array|null
     */
    protected static function parseInput(array $server, $input)
    {
        if (!$input) {
            return;
        }

        if (isset($server['CONTENT_TYPE']) && stripos($server['CONTENT_TYPE'], 'application/json') === 0) {
            return (array) json_decode($input, true) ?: null;
        }

        if (isset($server['REQUEST_METHOD']) && strtoupper($server['REQUEST_METHOD']) === 'PUT') {
            parse_str($input, $params);

            return (array) $params ?: null;
        }
    }
}
