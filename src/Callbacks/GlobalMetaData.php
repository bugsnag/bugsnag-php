<?php

namespace Bugsnag\Callbacks;

use Bugsnag\Configuration;
use Bugsnag\Error;

class GlobalMetaData
{
    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    protected $config;

    /**
     * Create a new global meta data callback instance.
     *
     * @param \Bugsnag\Configuration $config the configuration instance
     *
     * @return void
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Execute the global meta data callback.
     *
     * @param \Bugsnag\Error $error
     *
     * @return void
     */
    public function __invoke(Error $error)
    {
        if ($data = $this->config->getMetaData()) {
            $error->setMetaData($data);
        }
    }
}
