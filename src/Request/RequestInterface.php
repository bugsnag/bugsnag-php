<?php

namespace Bugsnag\Request;

interface RequestInterface
{
    /**
     * Are we currently processing a request?
     *
     * @return bool
     */
    public function isRequest();

    /**
     * Get the session data.
     *
     * @return array
     */
    public function getSessionData();

    /**
     * Get the cookie data.
     *
     * @return array
     */
    public function getCookieData();

    /**
     * Get the request formatted as meta data.
     *
     * @return array
     */
    public function getMetaData();

    /**
     * Get the request context.
     *
     * @return string|null
     */
    public function getContext();

    /**
     * Get the request user id.
     *
     * @return string|null
     */
    public function getUserId();
}
