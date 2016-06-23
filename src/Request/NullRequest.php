<?php

namespace Bugsnag\Request;

class NullRequest implements RequestInterface
{
    /**
     * Are we currently processing a request?
     *
     * @return bool
     */
    public function isRequest()
    {
        return false;
    }

    /**
     * Get the session data.
     *
     * @return array
     */
    public function getSessionData()
    {
        return [];
    }

    /**
     * Get the cookie data.
     *
     * @return array
     */
    public function getCookieData()
    {
        return [];
    }

    /**
     * Get the request formatted as meta data.
     *
     * @return array
     */
    public function getMetaData()
    {
        return [];
    }

    /**
     * Get the request context.
     *
     * @return string|null
     */
    public function getContext()
    {
        //
    }

    /**
     * Get the request user id.
     *
     * @return string|null
     */
    public function getUserId()
    {
        //
    }
}