<?php

namespace Bugsnag\Callbacks;

use Bugsnag\Error;
use Bugsnag\Request\ResolverInterface;

class RequestUser
{
    /**
     * The request resolver instance.
     *
     * @var \Bugsnag\Request\ResolverInterface
     */
    protected $resolver;

    /**
     * Create a new request user callback instance.
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
     * Execute the request user callback.
     *
     * @param \Bugsnag\Error $error
     *
     * @return void
     */
    public function __invoke(Error $error)
    {
        if ($id = $this->resolver->resolve()->getUserId()) {
            $error->setUser(['id' => $id]);
        }
    }
}
