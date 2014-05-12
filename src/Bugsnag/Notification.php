<?php

class Bugsnag_Notification
{
    private $config;
    private $errorQueue = array();

    public function __construct(Bugsnag_Configuration $config)
    {
        $this->config = $config;
    }

    public function addError($error, $passedMetaData=array())
    {
        // Check if this error should be sent to Bugsnag
        if (!$this->config->shouldNotify()) {
            return FALSE;
        }

        // Add global meta-data to error
        $error->setMetaData($this->config->metaData);

        // Add request meta-data to error
        if (Bugsnag_Request::isRequest()) {
            $error->setMetaData(Bugsnag_Request::getRequestMetaData());
        }

        // Add environment meta-data to error
        if (!empty($_ENV)) {
            $error->setMetaData(array("Environment" => $_ENV));
        }

        // Add user-specified meta-data to error
        $error->setMetaData($passedMetaData);

        // Run beforeNotify function (can cause more meta-data to be merged)
        if (isset($this->config->beforeNotifyFunction) && is_callable($this->config->beforeNotifyFunction)) {
            $beforeNotifyReturn = call_user_func($this->config->beforeNotifyFunction, $error);
        }

        // Skip this error if the beforeNotify function returned FALSE
        if (!isset($beforeNotifyReturn) || $beforeNotifyReturn !== FALSE) {
            $this->errorQueue[] = $error;

            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function toArray()
    {
        $events = array();
        foreach ($this->errorQueue as $error) {
            $errorArray = $error->toArray();

            if (!is_null($errorArray)) {
                $events[] = $errorArray;
            }
        }

        return array(
            'apiKey' => $this->config->apiKey,
            'notifier' => $this->config->notifier,
            'events' => $events
        );
    }

    public function deliver()
    {
        if (!empty($this->errorQueue)) {
            // Post the request to bugsnag
            $this->postJSON($this->config->getNotifyEndpoint(), $this->toArray());

            // Clear the error queue
            $this->errorQueue = array();
        }
    }

    private function transform($str) {
        if (mb_detect_encoding($str,'UTF-8',true) == false) {
            return utf8_encode($str);
        }
        return $str;
    }

    private function utf8($data) {
        if (is_array($data)) {
           $newData = array();
           foreach ($data as $key => $value) {
              if (is_array($value)) {
                  $newData[$this->transform($key)] = $this->utf8($value);
              }
              elseif (is_string($value)) {
                  $newData[$this->transform($key)] = $this->transform($value);
              }
           }
           return $newData;
        }
    }

    public function postJSON($url, $data)
    {
        $http = curl_init($url);
        $data = $this->utf8($data);

        // Default curl settings
        curl_setopt($http, CURLOPT_HEADER, false);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_POST, true);
        curl_setopt($http, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($http, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($http, CURLOPT_CONNECTTIMEOUT, $this->config->timeout);
        curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($http, CURLOPT_VERBOSE, false);

        // Apply proxy settings (if present)
        if (count($this->config->proxySettings)) {
            if (isset($this->config->proxySettings['host'])) {
                curl_setopt($http, CURLOPT_PROXY, $this->config->proxySettings['host']);
            }
            if (isset($this->config->proxySettings['port'])) {
                curl_setopt($http, CURLOPT_PROXYPORT, $this->config->proxySettings['port']);
            }
            if (isset($this->config->proxySettings['user'])) {
                $userPassword = $this->config->proxySettings['user'] . ':';
                $userPassword .= isset($this->config->proxySettings['password'])? $this->config->proxySettings['password'] : '';
                curl_setopt($http, CURLOPT_PROXYUSERPWD, $userPassword);
            }
        }

        // Execute the request and fetch the response
        $responseBody = curl_exec($http);
        $statusCode = curl_getinfo($http, CURLINFO_HTTP_CODE);

        if ($statusCode > 200) {
            error_log('Bugsnag Warning: Couldn\'t notify ('.$responseBody.')');
        }

        if (curl_errno($http)) {
            error_log('Bugsnag Warning: Couldn\'t notify (' . curl_error($http).')');
        }

        curl_close($http);

        return $statusCode;
    }
}
