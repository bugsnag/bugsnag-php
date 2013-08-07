<?php namespace Bugsnag;

// TODO: Autoloading?
require_once __DIR__."/Configuration.php";

class Client {
    /**
     * Initialize Bugsnag
     *
     * @param String $apiKey your Bugsnag API key
     */
    public function __construct($apiKey) {
        // TODO: Check for missing API key

        $this->config = new Configuration();
        $this->config->apiKey = $apiKey;

        // Attempt to determine a sensible default for projectRoot
        if(isset($_SERVER) && !empty($_SERVER['DOCUMENT_ROOT'])) {
            $this->setProjectRoot($_SERVER['DOCUMENT_ROOT']);
        }
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

    /* TODO */
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
        $this->config->projectRoot = $projectRoot;
        // $this->projectRootRegex = '/'.preg_quote($projectRoot, '/')."[\\/]?/i";
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
     * TODO
     */
    public function setMetaDataFunction($metaDataFunction) {
        $this->config->metaDataFunction = $metaDataFunction;
    }





    // Manual Notification
    public function notifyException($exception, $metaData=null) {
        $error = Bugsnag\Error::fromPHPException($this->config, $exception);
    }

    public function notifyError($name, $message, $metaData=null) {
        $backtrace = debug_backtrace();
        $firstFrame = array_shift($backtrace);

        $error = new Bugsnag\Error($config, $name, $message);
        $error->buildStacktrace($firstFrame["file"], $firstFrame["line"], $backtrace);
    }

    // Auto notification
    public function exceptionHandler($exception) {
        $this->notifyException($exception);
    }

    public function errorHandler($errno, $errstr, $errfile='', $errline=0, $errcontext=array()) {
        $backtrace = debug_backtrace();
        array_shift($backtrace);

        $error = Bugsnag\Error::fromPHPError($config, $errno, $errstr, $errfile, $errline, $backtrace);
    }
}

?>