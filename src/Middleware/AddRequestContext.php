<?php

namespace Bugsnag\Middleware;

use Bugsnag\Error;
use Bugsnag\Request\ResolverInterface;

class AddRequestContext
{
    /**
     * The request resolver instance.
     *
     * @var \Bugsnag\Request\ResolverInterface
     */
    protected $resolver;

    /**
     * Create a new add request context middleware instance.
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
     * Execute the add request context middleware.
     *
     * @param \Bugsnag\Error $error
     * @param callable       $next
     *
     * @return void
     */
    public function __invoke(Error $error, callable $next)
    {
        if ($context = $this->resolver->resolve()->getContext()) {
            $error->setContext($context);
        }

        $next($error);
    }
}
