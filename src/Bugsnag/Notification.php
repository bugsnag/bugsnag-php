<?php

class Bugsnag_Notification {
    private static $NOTIFIER = array(
        'name'      => 'Bugsnag PHP (Official)',
        'version'   => '2.0.1',
        'url'       => 'https://bugsnag.com'
    );

    private $config;
    private $errorQueue = array();

    public function __construct($config) {
        $this->config = $config;
    }

    public function addError($error, $passedMetaData=array()) {
        // Add global meta-data to error
        $error->setMetaData($this->config->metaData);

        // Add request meta-data to error
        if(Bugsnag_Request::isRequest()) {
            $error->setMetaData(Bugsnag_Request::getRequestMetaData());
        }

        // Add environment meta-data to error
        if(!empty($_ENV)) {
            $error->setMetaData(array("Environment" => $_ENV));
        }

        // Add user-specified meta-data to error
        $error->setMetaData($passedMetaData);

        // Run beforeNotify function (can cause more meta-data to be merged)
        if(isset($this->config->beforeNotifyFunction) && is_callable($this->config->beforeNotifyFunction)) {
            $beforeNotifyReturn = call_user_func($this->config->beforeNotifyFunction, $error);
        }

        // Skip this error if the beforeNotify function returned FALSE
        if(!isset($beforeNotifyReturn) || $beforeNotifyReturn !== FALSE) {
            $this->errorQueue[] = $error;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function toArray() {
        $events = array();
        foreach ($this->errorQueue as $error) {
            $errorArray = $error->toArray();

            if(!is_null($errorArray)) {
                $events[] = $errorArray;
            }
        }

        return array(
            'apiKey' => $this->config->apiKey,
            'notifier' => self::$NOTIFIER,
            'events' => $events
        );
    }

    public function deliver() {
        if(!empty($this->errorQueue)) {
            // Post the request to bugsnag
            $this->postJSON($this->config->getNotifyEndpoint(), $this->toArray());

            // Clear the error queue
            $this->errorQueue = array();
        }
    }

    public function postJSON($url, $data) {
        $http = curl_init($url);

        curl_setopt($http, CURLOPT_HEADER, false);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_POST, true);
        curl_setopt($http, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($http, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($http, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($http, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($http, CURLOPT_VERBOSE, false);

        $responseBody = curl_exec($http);
        $statusCode = curl_getinfo($http, CURLINFO_HTTP_CODE);

        if($statusCode > 200) {
            error_log('Bugsnag Warning: Couldn\'t notify ('.$responseBody.')');
        }

        curl_close($http);

        return $statusCode;
    }
}

?>