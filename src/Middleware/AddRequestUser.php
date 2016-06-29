<?php

namespace Bugsnag\Middleware;

use Bugsnag\Error;
use Bugsnag\Request\ResolverInterface;

class AddRequestUser
{
    /**
     * The request resolver instance.
     *
     * @var \Bugsnag\Request\ResolverInterface
     */
    protected $resolver;

    /**
     * Create a new add request user middleware instance.
     *
     * @param \Bugsnag\Request\ResolverInterface $resolver the request resolver instance
     *
     * @return void
     */
    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Execute the add request user middleware.
     *
     * @param \Bugsnag\Error $error
     * @param callable       $next
     *
     * @return bool
     */
    public function __invoke(Error $error, callable $next)
    {
        if ($id = $this->resolver->resolve()->getUserId()) {
            $error->setUser(['id' => $id]);
        }

        return $next($error);
    }
}
