<?php namespace Bugsnag;

class Notification {
    private static $NOTIFIER = array(
        'name'      => 'Bugsnag PHP (Official)',
        'version'   => '2.0.0',
        'url'       => 'https://bugsnag.com'
    );

    private $config;
    private $errorQueue = array();

    public function __construct($config) {
        $this->config = $config;
    }

    public function addError($error) {
        $this->errorQueue[] = $error;
    }

    public function deliver() {
        if(!empty($this->errorQueue)) {
            // Create an array from the error objects
            $events = array();
            foreach ($this->errorQueue as $error) {
                $events[] = $error->toArray();
            }

            // Post the request to bugsnag
            $statusCode = $this->postJSON($this->config->getNotifyEndpoint(), array(
                'apiKey' => $this->config->apiKey,
                'notifier' => self::$NOTIFIER,
                'events' => $events
            ));

            // Clear the error queue
            $this->errorQueue = array();
        }
    }

    public function postJSON($url, $data) {
        var_dump($data);
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