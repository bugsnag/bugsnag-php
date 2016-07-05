<?php

namespace Bugsnag\Callbacks;

use Bugsnag\Error;
use Bugsnag\Request\ResolverInterface;

class RequestSession
{
    /**
     * The request resolver instance.
     *
     * @var \Bugsnag\Request\ResolverInterface
     */
    protected $resolver;

    /**
     * Create a new request session callback instance.
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
     * Execute the request session callback.
     *
     * @param \Bugsnag\Error $error
     *
     * @return void
     */
    public function __invoke(Error $error)
    {
        if ($data = $this->resolver->resolve()->getSession()) {
            $error->setMetaData(['session' => $data]);
        }
    }
}
