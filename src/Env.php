<?php

namespace Bugsnag;

class Env
{
    /**
     * Reads an environment variable from $_ENV, $_SERVER or via getenv(). Supports a thread-safe read via the
     * superglobals, but falls back on getenv() to allow for other methods of setting environment data. See this article
     * for more background context: https://mattallan.me/posts/how-php-environment-variables-actually-work/.
     *
     * Copied from phpdotenv: https://github.com/vlucas/phpdotenv/blob/2.6/src/Loader.php#L291
     *
     * @param $name
     *
     * @return array|false|mixed|string|null
     */
    public function get($name)
    {
        switch (true) {
            case array_key_exists($name, $_ENV):
                return $_ENV[$name];

            case array_key_exists($name, $_SERVER):
                return $_SERVER[$name];

            default:
                $value = getenv($name);

                return $value === false ? null : $value; // switch getenv default to null
        }
    }
}
