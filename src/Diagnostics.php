<?php

namespace Bugsnag;

use Bugsnag\Request\ResolverInterface;

class Diagnostics
{
    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    protected $config;

    /**
     * The request resolver instance.
     *
     * @var \Bugsnag\Request\ResolverInterface
     */
    protected $resolver;

    /**
     * Create a new diagnostics instance.
     *
     * @param \Bugsnag\Configuration             $config   the configuration instance
     * @param \Bugsnag\Request\ResolverInterface $resolver the request resolver instance
     *
     * @return void
     */
    public function __construct(Configuration $config, ResolverInterface $resolver)
    {
        $this->config = $config;
        $this->resolver = $resolver;
    }

    /**
     * Get the error context.
     *
     * @return array
     */
    public function getContext()
    {
        return $this->resolver->resolve()->getContext();
    }

    /**
     * Get the current user.
     *
     * @return array
     */
    public function getUser()
    {
        $id = $this->resolver->resolve()->getUserId();

        return is_null($id) ? [] : ['id' => $id];
    }
}
