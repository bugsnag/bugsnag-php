<?php

namespace Bugsnag\Callbacks;

use Bugsnag\Error;
use Bugsnag\Request\ResolverInterface;

class RequestCookies
{
    /**
     * The request resolver instance.
     *
     * @var \Bugsnag\Request\ResolverInterface
     */
    protected $resolver;

    /**
     * Create a new request cookies callback instance.
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
     * Execute the request cookies callback.
     *
     * @param \Bugsnag\Error $error
     *
     * @return void
     */
    public function __invoke(Error $error)
    {
        if ($data = $this->resolver->resolve()->getCookie()) {
            $error->setMetaData(['cookies' => $data]);
        }
    }
}
