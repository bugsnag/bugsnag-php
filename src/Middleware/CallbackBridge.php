<?php

namespace Bugsnag\Middleware;

use Bugsnag\Error;

class CallbackBridge
{
    /**
     * The callback to run.
     *
     * @var callable
     */
    protected $callback;

    /**
     * Create a new callback bridge middleware instance.
     *
     * @param callable $callback the callback to run
     *
     * @return void
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Execute the add callback bridge middleware.
     *
     * @param \Bugsnag\Error $error
     * @param callable       $next
     *
     * @return void
     */
    public function __invoke(Error $error, callable $next)
    {
        $callback = $this->callback;

        if ($callback($error) !== false) {
            $next($error);
        }
    }
}
