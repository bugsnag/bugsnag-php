<?php

namespace Bugsnag;

class Diagnostics
{
    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    private $config;

    /**
     * Create a new diagnostics instance.
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
     * Get the application information.
     *
     * @return array
     */
    public function getAppData()
    {
        $appData = [];

        if (!is_null($this->config->appVersion)) {
            $appData['version'] = $this->config->appVersion;
        }

        if (!is_null($this->config->releaseStage)) {
            $appData['releaseStage'] = $this->config->releaseStage;
        }

        if (!is_null($this->config->type)) {
            $appData['type'] = $this->config->type;
        }

        return $appData;
    }

    /**
     * Get the device information.
     *
     * @return array
     */
    public function getDeviceData()
    {
        return [
            'hostname' => $this->config->get('hostname', php_uname('n')),
        ];
    }

    /**
     * Get the error context.
     *
     * @return array
     */
    public function getContext()
    {
        return $this->config->get('context', Request::getContext());
    }

    /**
     * Get the current user.
     *
     * @return array
     */
    public function getUser()
    {
        $defaultUser = [];
        $userId = Request::getUserId();

        if (!is_null($userId)) {
            $defaultUser['id'] = $userId;
        }

        return $this->config->get('user', $defaultUser);
    }
}
