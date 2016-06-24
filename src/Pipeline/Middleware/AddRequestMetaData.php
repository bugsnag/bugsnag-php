<?php

namespace Bugsnag\Pipeline\Middleware;

use Bugsnag\Error;
use Bugsnag\Request\ResolverInterface;

class AddRequestMetaData
{
    /**
     * The request resolver instance.
     *
     * @var \Bugsnag\Request\ResolverInterface
     */
    protected $resolver;

    /**
     * Create a new add request meta data middleware instance.
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
     * Execute the add request meta data middleware.
     *
     * @param \Bugsnag\Error $error
     * @param callable       $next
     *
     * @return bool
     */
    public function __invoke(Error $error, callable $next)
    {
        if ($data = $this->resolver->resolve()->getMetaData()) {
            $error->setMetaData($data);
        }

        return $next($error);
    }
}
