<?php

namespace Bugsnag\Callbacks;

use Bugsnag\Error;
use Exception;

class CustomUser
{
    /**
     * The user resolver.
     *
     * @var callable
     */
    protected $resolver;

    /**
     * Create a new custom user callback instance.
     *
     * @param callable $resolver the user resolver
     *
     * @return void
     */
    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Execute the user data callback.
     *
     * @param \Bugsnag\Error $error
     *
     * @return void
     */
    public function __invoke(Error $error)
    {
        $resolver = $this->resolver;

        try {
            if ($user = $resolver()) {
                $error->setUser($user);
            }
        } catch (Exception $e) {
            // Ignore any errors.
        }
    }
}
