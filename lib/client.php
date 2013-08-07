<?php namespace Bugsnag;

require_once(__DIR__."/http_client.php");

/**
 * Bugsnag PHP Client
 * https://bugsnag.com
 *
 * Supports PHP 5.2+
 *
 * Full documentation here: 
 * https://bugsnag.com/docs/notifiers/php
 *
 * @package     Bugsnag
 * @version     1.0.7
 * @author      James Smith <notifiers@bugsnag.com>
 * @copyright   (c) 2013 Bugsnag
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Client {
    // "Constants"
    private static $NOTIFIER = array(
        'name'      => 'Bugsnag PHP (Official)',
        'version'   => '1.0.7',
        'url'       => 'https://bugsnag.com'
    );                                                                      

    private static $ERROR_NAMES = array (
        E_ERROR             => 'PHP Fatal Error',
        E_PARSE             => 'PHP Parse Error',
        E_COMPILE_ERROR     => 'PHP Compile Error',
        E_CORE_ERROR        => 'PHP Core Error',
        E_NOTICE            => 'PHP Notice',
        E_STRICT            => 'PHP Strict',
        E_WARNING           => 'PHP Warning',
        E_CORE_WARNING      => 'PHP Core Warning',
        E_COMPILE_WARNING   => 'PHP Compile Warning',
        E_RECOVERABLE_ERROR => 'PHP Recoverable Error',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',

        // PHP 5.2 compatibility
        8192                => 'PHP Deprecated',
        16384               => 'User Deprecated'
    );

    private static $FATAL_ERRORS = array(
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING,
        E_STRICT
    );

    // Configuration state
    private $apiKey;
    private $releaseStage = 'production';
    private $notifyReleaseStages;
    private $useSSL = true;
    private $projectRoot;
    private $filters = array('password');
    private $endpoint = 'notify.bugsnag.com';
    private $context;
    private $userId;
    private $beforeNotifyFunction;
    private $metaDataFunction;
    private $errorReportingLevel;

    private $projectRootRegex;
    private $errorQueue = array();
    

    /**
     * Initialize Bugsnag
     *
     * @param String $apiKey your Bugsnag API key
     */
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;

        // Attempt to determine a sensible default for projectRoot
        if(isset($_SERVER) && !empty($_SERVER['DOCUMENT_ROOT']) && !isset($this->projectRoot)) {
            self::setProjectRoot($_SERVER['DOCUMENT_ROOT']);
        }

        // Register a shutdown function to check for fatal errors
        register_shutdown_function(array($this, "fatalErrorHandler"));
    }

    /**
     * Set your release stage, eg "production" or "development"
     *
     * @param String $releaseStage the app's current release stage
     */
    public function setReleaseStage($releaseStage) {
        $this->releaseStage = $releaseStage;
    }

    /**
     * Set which release stages should be allowed to notify Bugsnag
     * eg array("production", "development")
     *
     * @param Array $notifyReleaseStages array of release stages to notify for
     */
    public function setNotifyReleaseStages($notifyReleaseStages) {
        $this->notifyReleaseStages = $notifyReleaseStages;
    }

    /* TODO */
    public function setEndpoint($endpoint) {
        $this->endpoint = $endpoint;
    }

    /**
     * Set whether or not to use SSL when notifying bugsnag
     *
     * @param Boolean $useSSL whether to use SSL
     */
    public function setUseSSL($useSSL) {
        $this->useSSL = $useSSL;
    }

    /**
     * Set the absolute path to the root of your application. 
     * We use this to help with error grouping and to highlight "in project"
     * stacktrace lines.
     *
     * @param String $projectRoot the root path for your application
     */
    public function setProjectRoot($projectRoot) {
        $this->projectRoot = $projectRoot;
        $this->projectRootRegex = '/'.preg_quote($projectRoot, '/')."[\\/]?/i";
    }

    /**
     * Set the strings to filter out from metaData arrays before sending then
     * to Bugsnag. Eg. array("password", "credit_card")
     *
     * @param Array $filters an array of metaData filters 
     */
    public function setFilters($filters) {
        $this->filters = $filters;
    }

    /**
     * Set the unique userId representing the current request.
     *
     * @param String $userId the current user id
     */
    public function setUserId($userId) {
        $this->userId = $userId;
    }

    /**
     * Set a context representing the current type of request, or location in code.
     *
     * @param String $context the current context
     */
    public function setContext($context) {
        $this->context = $context;
    }

    /**
     * DEPRECATED: Please use `setBeforeNotifyFunction` instead.
     * Set a custom metadata generation function to call before notifying
     * Bugsnag of an error.
     */
    public function setMetaDataFunction($metaDataFunction) {
        $this->metaDataFunction = $metaDataFunction;
    }

    /**
     * Set a custom function to call before notifying Bugsnag of an error.
     * You can use this to call your own error handling functions, or to
     * add custom tabs of data to each error on your Bugsnag dashboard.
     *
     * To add custom tabs of meta-data, simply add to the $metaData array
     * that is passed as the first parameter to your function.
     *
     * @param Callback $beforeNotifyFunction a function that will be called
     *        before notifying Bugsnag of errors. Eg:
     *
     *        function before_bugsnag_notify($metaData) {
     *            $metaData = array(
     *                "user" => array(
     *                    "name" => "James",
     *                    "email" => "james@example.com"
     *                )
     *            )
     *        }
     */
    public function setBeforeNotifyFunction($beforeNotifyFunction) {
        $this->beforeNotifyFunction = $beforeNotifyFunction;
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
        $this->errorReportingLevel = $errorReportingLevel;
    }

    /**
     * Notify Bugsnag of a non-fatal/handled exception
     *
     * @param Exception $exception the exception to notify Bugsnag about
     * @param Array $metaData optional metaData to send with this error
     */
    public function notifyException($exception, $metaData=null) {
        // Build a sensible stacktrace
        $stacktrace = self::buildStacktrace($exception->getFile(), $exception->getLine(), $exception->getTrace());

        // Send the notification to bugsnag
        self::notify(get_class($exception), $exception->getMessage(), $stacktrace, $metaData);
    }

    /**
     * Notify Bugsnag of a non-fatal/handled error
     *
     * @param String $errorName the name of the error, a short (1 word) string
     * @param String $errorMessage the error message
     * @param Array $metaData optional metaData to send with this error
     */
    public function notifyError($errorName, $errorMessage, $metaData=null) {
        // Get the stack, remove the current function, build a sensible stacktrace]
        $backtrace = debug_backtrace();
        $firstFrame = array_shift($backtrace);
        $stacktrace = self::buildStacktrace($firstFrame["file"], $firstFrame["line"], $backtrace);

        // Send the notification to bugsnag
        self::notify($errorName, $errorMessage, $stacktrace, $metaData);
    }



    // Exception handler callback, should only be called internally by PHP's set_exception_handler
    public function exceptionHandler($exception) {
        self::notifyException($exception);
    }

    // Exception handler callback, should only be called internally by PHP's set_error_handler
    public function errorHandler($errno, $errstr, $errfile='', $errline=0, $errcontext=array()) {
        // Check if we should notify Bugsnag about errors of this type
        if(!self::shouldNotify($errno)) {
            return;
        }

        // Get the stack, remove the current function, build a sensible stacktrace]
        // TODO: Add a method to remove any user's set_error_handler functions from this stacktrace
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        $stacktrace = self::buildStacktrace($errfile, $errline, $backtrace);

        // Send the notification to bugsnag
        self::notify(self::$ERROR_NAMES[$errno], $errstr, $stacktrace);
    }

    // Shutdown handler callback, should only be called internally by PHP's register_shutdown_function
    public function fatalErrorHandler() {
        // Get last error
        $lastError = error_get_last();

        // Check if a fatal error caused this shutdown
        if(!is_null($lastError) && in_array($lastError['type'], self::$FATAL_ERRORS)) {
            // NOTE: We can't get the error's backtrace here :(
            $stacktrace = self::buildStacktrace($lastError['file'], $lastError['line']);

            // Send the notification to bugsnag
            self::notify(self::$ERROR_NAMES[$lastError['type']], $lastError['message'], $stacktrace);
        }

        // Check if we should flush errors
        if(self::sendErrorsOnShutdown()) {
            self::flushErrorQueue();
        }
    }



    // Private methods
    private function notify($errorName, $errorMessage, $stacktrace=null, $passedMetaData=null) {
        $customMetaData = array();

        // Check if we should notify
        if(is_array($this->notifyReleaseStages) && !in_array($this->releaseStage, $this->notifyReleaseStages)) {
            return;
        }

        // Check we have at least an api_key
        if(!isset($this->apiKey)) {
            error_log('Bugsnag Warning: No API key configured, couldn\'t notify');
            return;
        }

        // For backwards compatibility
        if(isset($this->metaDataFunction) && is_callable($this->metaDataFunction)) {
            $customMetaData = call_user_func($this->metaDataFunction);
        }

        // Call the custom beforeNotify function
        if(isset($this->beforeNotifyFunction) && is_callable($this->beforeNotifyFunction)) {
            // call_user_func($this->beforeNotifyFunction, &$customMetaData);
        }

        // Build the error payload to send to Bugsnag
        $error = array(
            'userId' => self::getUserId(),
            'releaseStage' => $this->releaseStage,
            'context' => self::getContext(),
            'exceptions' => array(array(
                'errorClass' => $errorName,
                'message' => $errorMessage,
                'stacktrace' => $stacktrace
            )),
            'metaData' => self::getMetaData($passedMetaData, $customMetaData)
        );

        // Add this error payload to the send queue
        $this->errorQueue[] = $error;

        // Flush the queue immediately unless we are batching errors
        if(!self::sendErrorsOnShutdown()) {
            self::flushErrorQueue();
        }
    }

    private function sendErrorsOnShutdown() {
        return self::isRequest();
    }

    private function flushErrorQueue() {
        if(!empty($this->errorQueue)) {
            // Post the request to bugsnag
            $statusCode = HttpClient::post(self::getEndpoint(), array(
                'apiKey' => $this->apiKey,
                'notifier' => self::$NOTIFIER,
                'events' => $this->errorQueue
            ));

            // Clear the error queue
            $this->errorQueue = array();
        }
    }

    private function buildStacktrace($topFile, $topLine, $backtrace=null) {
        $stacktrace = array();

        if(!is_null($backtrace)) {
            // PHP backtrace's are misaligned, we need to shift the file/line down a frame
            foreach ($backtrace as $line) {
                $stacktrace[] = self::buildStacktraceFrame($topFile, $topLine, $line['function']);

                if(isset($line['file']) && isset($line['line'])) {
                    $topFile = $line['file'];
                    $topLine = $line['line'];
                } else {
                    $topFile = "[internal]";
                    $topLine = 0;
                }
            }

            // Add a final stackframe for the "main" method
            $stacktrace[] = self::buildStacktraceFrame($topFile, $topLine, '[main]');
        } else {
            // No backtrace given, show what we know
            $stacktrace[] = self::buildStacktraceFrame($topFile, $topLine, '[unknown]');
        }

        return $stacktrace;
    }

    private function buildStacktraceFrame($file, $line, $method) {
        // Check if this frame is inProject
        $inProject = !is_null($this->projectRoot) && preg_match($this->projectRootRegex, $file);

        // Strip out projectRoot from start of file path
        if($inProject) {
            $file = preg_replace($this->projectRootRegex, '', $file);
        }

        // Construct and return the frame
        return array(
            'file' => $file,
            'lineNumber' => $line,
            'method' => $method,
            'inProject' => $inProject
        );
    }

    private function getEndpoint() {
        return $this->useSSL ? 'https://'.$this->endpoint : 'http://'.$this->endpoint;
    }

    private function isRequest() {
        return isset($_SERVER['REQUEST_METHOD']);
    }

    private function getMetaData($passedMetaData=array(), $customMetaData=array()) {
        $metaData = array();

        // Add http request info
        if(self::isRequest()) {
            $metaData = array_merge_recursive($metaData, self::getRequestData());
        }

        // Add environment info
        if(!empty($_ENV)) {
            $metaData['environment'] = $_ENV;
        }

        // Merge user-defined metadata if custom function is specified
        if(!is_null($customMetaData) && is_array($customMetaData)) {
            $metaData = array_merge_recursive($metaData, $customMetaData);
        }

        // Merge $passedMetaData
        if(!empty($passedMetaData)) {
            $metaData = array_merge_recursive($metaData, $passedMetaData);
        }

        // Filter metaData according to $this->filters
        $metaData = self::applyFilters($metaData);

        return $metaData;
    }

    private function getRequestData() {
        $requestData = array();

        // Request Tab
        $requestData['request'] = array();
        $requestData['request']['url'] = self::getCurrentUrl();
        if(isset($_SERVER['REQUEST_METHOD'])) {
            $requestData['request']['httpMethod'] = $_SERVER['REQUEST_METHOD'];
        }

        if(!empty($_POST)) {
            $requestData['request']['params'] = $_POST;
        } else {
            if(isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') === 0) {
                $requestData['request']['params'] = json_decode(file_get_contents('php://input'));
            }
        }

        $requestData['request']['ip'] = self::getRequestIp();
        if(isset($_SERVER['HTTP_USER_AGENT'])) {
            $requestData['request']['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        if(function_exists("getallheaders")) {
            $headers = getallheaders();
            if(!empty($headers)) {
                $requestData['request']['headers'] = $headers;
            }
        }

        // Session Tab
        if(!empty($_SESSION)) {
            $requestData['session'] = $_SESSION;
        }

        // Cookies Tab
        if(!empty($_COOKIE)) {
            $requestData['cookies'] = $_COOKIE;
        }

        return $requestData;
    }

    private function getCurrentUrl() {
        $schema = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';

        return $schema.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    private function getRequestIp() {
        return isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
    }

    private function getContext() {
        if($this->context) {
            return $this->context;
        } elseif(self::isRequest() && isset($_SERVER['REQUEST_METHOD']) && isset($_SERVER["REQUEST_URI"])) {
            return $_SERVER['REQUEST_METHOD'] . ' ' . strtok($_SERVER["REQUEST_URI"], '?');
        } else {
            return null;
        }
    }

    private function getUserId() {
        if($this->userId) {
            return $this->userId;
        } elseif(self::isRequest()) {
            return self::getRequestIp();
        } else {
            return null;
        }
    }

    private function applyFilters($metaData) {
        if(!empty($this->filters)) {
            $cleanMetaData = array();

            foreach ($metaData as $key => $value) {
                $shouldFilter = false;
                foreach($this->filters as $filter) {
                    if(strpos($key, $filter) !== false) {
                        $shouldFilter = true;
                        break;
                    }
                }

                if($shouldFilter) {
                    $cleanMetaData[$key] = '[FILTERED]';
                } else {
                    if(is_array($value)) {
                        $cleanMetaData[$key] = self::applyFilters($value);
                    } else {
                        $cleanMetaData[$key] = $value;
                    }
                }
            }
            
            return $cleanMetaData;
        } else {
            return $metaData;
        }
    }

    private function shouldNotify($errno) {
        if(isset($this->errorReportingLevel)) {
            return $this->errorReportingLevel & $errno;
        } else {
            return error_reporting() & $errno;
        }
    }
}

?>