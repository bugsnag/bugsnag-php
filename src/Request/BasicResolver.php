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
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            return new NullRequest();
        }

        return new PhpRequest($_SERVER, $_SESSION, $_COOKIE, static::getRequestHeaders($_SERVER), static::getInputParams($_SERVER, $_POST));
    }

    /**
     * Get the request headers.
     *
     * Note how we're caching this result for ever, accorss all instances.
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

        if (function_exists('getallheaders')) {
            return $headers = getallheaders();
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
     * Note how we're caching this result for ever, accorss all instances.
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

        $result = $post ?: (static::parseInput($server, file_get_contents('php://input')) ?: false);

        return $result ?: null;
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
            $result = (array) json_decode($input, true);
        }

        if (isset($server['REQUEST_METHOD']) && strtoupper($server['REQUEST_METHOD']) === 'PUT') {
            parse_str($input, $params);
            $result = isset($result) ? array_merge($result, $params) : $params;
        }

        if (isset($result)) {
            return $result;
        }
    }
}
