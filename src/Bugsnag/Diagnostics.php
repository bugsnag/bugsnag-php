<?php

class Bugsnag_Diagnostics
{
    private $config;

    public function __construct(Bugsnag_Configuration $config)
    {
        $this->config = $config;
    }

    public function getAppData()
    {
        return array(
            'version' => $this->config->appVersion,
            'releaseStage' => $this->config->releaseStage,
            'type' => $this->config->type
        );
    }

    public function getDeviceData()
    {
        return array(
            'hostname' => $this->config->get('hostname', php_uname('n'))
        );
    }

    public function getContext()
    {
        return $this->config->get('context', Bugsnag_Request::getContext());
    }

    public function getUser()
    {
        return $this->config->get('user', array('id' => Bugsnag_Request::getUserId()));
    }
}
