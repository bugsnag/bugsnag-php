<?php

namespace Bugsnag;

class Diagnostics
{
    private $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

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

    public function getDeviceData()
    {
        return [
            'hostname' => $this->config->get('hostname', php_uname('n')),
        ];
    }

    public function getContext()
    {
        return $this->config->get('context', Request::getContext());
    }

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
