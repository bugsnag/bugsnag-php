<?php

namespace Bugsnag\Callbacks;

use Bugsnag\Error;
use Bugsnag\Request\ResolverInterface;

class RequestContext
{
    /**
     * The request resolver instance.
     *
     * @var \Bugsnag\Request\ResolverInterface
     */
    protected $resolver;

    /**
     * Create a new request context callback instance.
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
     * Execute the request context callback.
     *
     * @param \Bugsnag\Error $error
     *
     * @return void
     */
    public function __invoke(Error $error)
    {
        if ($context = $this->resolver->resolve()->getContext()) {
            $error->setContext($context);
        }
    }
}
