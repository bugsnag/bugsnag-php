<?php

class Bugsnag_Client {
    public $config;
    private $notification;

    /**
     * Initialize Bugsnag
     *
     * @param String $apiKey your Bugsnag API key
     */
    public function __construct($apiKey) {
        // Check API key has been passed
        if(!is_string($apiKey)) {
            throw new Exception("Bugsnag Error: Invalid API key");
        }

        // Create a configuration object
        $this->config = new Bugsnag_Configuration();
        $this->config->apiKey = $apiKey;

        // Attempt to determine a sensible default for projectRoot
        if(isset($_SERVER) && !empty($_SERVER['DOCUMENT_ROOT'])) {
            $this->setProjectRoot($_SERVER['DOCUMENT_ROOT']);
        }

        // Register a shutdown function to check for fatal errors
        // and flush any buffered errors
        register_shutdown_function(array($this, 'fatalErrorHandler'));
    }

    /**
     * Set your release stage, eg "production" or "development"
     *
     * @param String $releaseStage the app's current release stage
     */
    public function setReleaseStage($releaseStage) {
        $this->config->releaseStage = $releaseStage;
    }

    /**
     * Set which release stages should be allowed to notify Bugsnag
     * eg array("production", "development")
     *
     * @param Array $notifyReleaseStages array of release stages to notify for
     */
    public function setNotifyReleaseStages($notifyReleaseStages) {
        $this->config->notifyReleaseStages = $notifyReleaseStages;
    }

    /**
     * Set which Bugsnag endpoint to send errors to.
     *
     * @param String $endpoint endpoint URL
     */
    public function setEndpoint($endpoint) {
        $this->config->endpoint = $endpoint;
    }

    /**
     * Set whether or not to use SSL when notifying bugsnag
     *
     * @param Boolean $useSSL whether to use SSL
     */
    public function setUseSSL($useSSL) {
        $this->config->useSSL = $useSSL;
    }

    /**
     * Set the absolute path to the root of your application. 
     * We use this to help with error grouping and to highlight "in project"
     * stacktrace lines.
     *
     * @param String $projectRoot the root path for your application
     */
    public function setProjectRoot($projectRoot) {
        $this->config->setProjectRoot($projectRoot);
    }

    /**
     * Set the strings to filter out from metaData arrays before sending then
     * to Bugsnag. Eg. array("password", "credit_card")
     *
     * @param Array $filters an array of metaData filters 
     */
    public function setFilters($filters) {
        $this->config->filters = $filters;
    }

    /**
     * Set the unique userId representing the current request.
     *
     * @param String $userId the current user id
     */
    public function setUserId($userId) {
        $this->config->userId = $userId;
    }

    /**
     * Set a context representing the current type of request, or location in code.
     *
     * @param String $context the current context
     */
    public function setContext($context) {
        $this->config->context = $context;
    }

    /**
     * Set custom metadata to send to Bugsnag with every error. You can use
     * this to add custom tabs of data to each error on your Bugsnag dashboard
     *
     * @param Array $metaData an array of arrays of custom data. Eg:
     *        array(
     *            "user" => array(
     *                "name" => "James",
     *                "email" => "james@example.com"
     *            )
     *        )
     */
    public function setMetaData($metaData) {
        $this->config->metaData = $metaData;
    }

    /**
     * Set a custom metadata generation function to call before notifying
     * Bugsnag of an error. You can use this to add custom tabs of data
     * to each error on your Bugsnag dashboard.
     *
     * @param Callback $metaDataFunction a function that should return an
     *        array of arrays of custom data. Eg:
     *        array(
     *            "user" => array(
     *                "name" => "James",
     *                "email" => "james@example.com"
     *            )
     *        )
     */
    public function setMetaDataFunction($metaDataFunction) {
        $this->config->metaDataFunction = $metaDataFunction;
    }

    /**
     * Set a custom function to call before notifying Bugsnag of an error.
     * You can use this to call your own error handling functions, or to add
     * custom tabs of data to each error on your Bugsnag dashboard.
    */
    public function setBeforeNotifyFunction($beforeNotifyFunction) {
        $this->config->beforeNotifyFunction = $beforeNotifyFunction;
    }

    /**
     * Set Bugsnag's error reporting level.
     * If this is not set, we'll use your current PHP error_reporting value
     * from your ini file or error_reporting(...) calls.
     *
     * @param Integer $errorReportingLevel the error reporting level integer
     *                exactly as you would pass to PHP's error_reporting
     */
    public function setErrorReportingLevel($errorReportingLevel) {
        $this->config->errorReportingLevel = $errorReportingLevel;
    }

    /**
     * Sets whether Bugsnag should be automatically notified of unhandled
     * exceptions and errors.
     *
     * @param Boolean $autoNotify whether to auto notify or not
     */
    public function setAutoNotify($autoNotify) {
        $this->config->autoNotify = $autoNotify;
    }

    /**
     * Set the notification object to use for batching up notifications.
     */
    public function setNotification($notification) {
        $this->notification = $notification;
    }

    /**
     * Notify Bugsnag of a non-fatal/handled exception
     *
     * @param Exception $exception the exception to notify Bugsnag about
     * @param Array $metaData optional metaData to send with this error
     */
    public function notifyException($exception, $metaData=null) {
        $error = Bugsnag_Error::fromPHPException($this->config, $exception);
        $this->notify($error, $metaData);
    }

    /**
     * Notify Bugsnag of a non-fatal/handled error
     *
     * @param String $errorName the name of the error, a short (1 word) string
     * @param String $errorMessage the error message
     * @param Array $metaData optional metaData to send with this error
     */
    public function notifyError($name, $message, $metaData=null) {
        $error = Bugsnag_Error::fromNamedError($this->config, $name, $message);
        $this->notify($error, $metaData);
    }

    // Exception handler callback, should only be called internally by PHP's set_exception_handler
    public function exceptionHandler($exception) {
        $error = Bugsnag_Error::fromPHPException($this->config, $exception);

        if(!$error->shouldIgnore() && $this->config->autoNotify) {
            $this->notify($error);
        }
    }

    // Exception handler callback, should only be called internally by PHP's set_error_handler
    public function errorHandler($errno, $errstr, $errfile='', $errline=0, $errcontext=array()) {
        $error = Bugsnag_Error::fromPHPError($this->config, $errno, $errstr, $errfile, $errline);

        if(!$error->shouldIgnore() && $this->config->autoNotify) {
            $this->notify($error);
        }
    }

    // Shutdown handler callback, should only be called internally by PHP's register_shutdown_function
    public function fatalErrorHandler() {
        // Get last error
        $lastError = error_get_last();

        // Check if a fatal error caused this shutdown
        if(!is_null($lastError) && in_array($lastError['type'], Bugsnag_Error::$FATAL_ERRORS)) {
            $error = Bugsnag_Error::fromPHPFatalError($this->config, $lastError['type'], $lastError['message'], $lastError['file'], $lastError['line']);

            if(!$error->shouldIgnore() && $this->config->autoNotify) {
                $this->notify($error);
            }
        }

        // Flush any buffered errors
        if($this->notification) {
            $this->notification->deliver();
            $this->notification = null;
        }
    }

    // Batches up errors into notifications for later sending
    public function notify($error, $metaData=array()) {
        // Add request metadata to error
        if(Bugsnag_Request::isRequest()) {
            $error->setMetaData(array("Request" => Bugsnag_Request::getRequestMetaData()));
        }

        // Add user-specified metaData to error
        $error->setMetaData($metaData);

        // Queue or send the error
        if($this->sendErrorsOnShutdown()) {
            // Create a batch notification unless we already have one
            if(is_null($this->notification)) {
                $this->notification = new Bugsnag_Notification($this->config);
            }

            // Add this error to the notification
            $this->notification->addError($error);
        } else {
            // Create and deliver notification immediatelt
            $notif = new Bugsnag_Notification($this->config);
            $notif->addError($error);
            $notif->deliver();
        }
    }

    // Should we send errors immediately or on shutdown
    private static function sendErrorsOnShutdown() {
        return Bugsnag_Request::isRequest();
    }
}

?>