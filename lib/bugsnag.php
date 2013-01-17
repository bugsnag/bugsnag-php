<?php

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
 * @author      James Smith <notifiers@bugsnag.com>
 * @copyright   (c) 2013 Bugsnag
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Bugsnag {
    // "Constants"
    private static $NOTIFIER = array(
        'name' => 'Bugsnag PHP (Official)',
        'version' => '1.0.0',
        'url' => 'https://bugsnag.com'
    );                                                                      

    private static $ERROR_NAMES = array (
        \E_ERROR             => 'PHP Fatal Error',
        \E_PARSE             => 'PHP Parse Error',
        \E_COMPILE_ERROR     => 'PHP Compile Error',
        \E_CORE_ERROR        => 'PHP Core Error',
        \E_NOTICE            => 'PHP Notice',
        \E_STRICT            => 'PHP Strict',
        \E_DEPRECATED        => 'PHP Deprecated',
        \E_WARNING           => 'PHP Warning',
        \E_CORE_WARNING      => 'PHP Core Warning',
        \E_COMPILE_WARNING   => 'PHP Compile Warning',
        \E_RECOVERABLE_ERROR => 'PHP Recoverable Error',
        \E_USER_ERROR        => 'User Error',
        \E_USER_WARNING      => 'User Warning',
        \E_USER_NOTICE       => 'User Notice',
        \E_USER_DEPRECATED   => 'User Deprecated'
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
    private static $apiKey;
    private static $releaseStage = 'production';
    private static $notifyReleaseStages = array('production');
    private static $useSSL = true;
    private static $projectRoot;
    private static $filters = array('password');
    private static $endpoint = 'notify.bugsnag.com';
    private static $context;
    private static $userId;
    private static $metaDataFunction;
    private static $errorReportingLevel;

    private static $registeredShutdown = false;
    private static $projectRootRegex;
    private static $errorQueue = array();
    

    /**
     * Initialize Bugsnag
     *
     * @param String $apiKey your Bugsnag API key
     */
    public static function register($apiKey) {
        self::$apiKey = $apiKey;

        // Attempt to determine a sensible default for projectRoot
        if(isset($_SERVER) && !empty($_SERVER['DOCUMENT_ROOT']) && !isset(self::$projectRoot)) {
            self::setProjectRoot($_SERVER['DOCUMENT_ROOT']);
        }

        // Register a shutdown function to check for fatal errors
        if(!self::$registeredShutdown) {
            register_shutdown_function('Bugsnag::fatalErrorHandler');
            self::$registeredShutdown = true;
        }
    }

    /**
     * Set your release stage, eg "production" or "development"
     *
     * @param String $releaseStage the app's current release stage
     */
    public static function setReleaseStage($releaseStage) {
        self::$releaseStage = $releaseStage;
    }

    /**
     * Set which release stages should be allowed to notify Bugsnag
     * eg array("production", "development")
     *
     * @param Array $notifyReleaseStages array of release stages to notify for
     */
    public static function setNotifyReleaseStages($notifyReleaseStages) {
        self::$notifyReleaseStages = $notifyReleaseStages;
    }

    /**
     * Set whether or not to use SSL when notifying bugsnag
     *
     * @param Boolean $useSSL whether to use SSL
     */
    public static function setUseSSL($useSSL) {
        self::$useSSL = $useSSL;
    }

    /**
     * Set the absolute path to the root of your application. 
     * We use this to help with error grouping and to highlight "in project"
     * stacktrace lines.
     *
     * @param String $projectRoot the root path for your application
     */
    public static function setProjectRoot($projectRoot) {
        self::$projectRoot = $projectRoot;
        self::$projectRootRegex = '/'.preg_quote($projectRoot, '/')."[\\/]?/i";
    }

    /**
     * Set the strings to filter out from metaData arrays before sending then
     * to Bugsnag. Eg. array("password", "credit_card")
     *
     * @param Array $filters an array of metaData filters 
     */
    public static function setFilters($filters) {
        self::$filters = $filters;
    }

    /**
     * Set the unique userId representing the current request.
     *
     * @param String $userId the current user id
     */
    public static function setUserId($userId) {
        self::$userId = $userId;
    }

    /**
     * Set a context representing the current type of request, or location in code.
     *
     * @param String $context the current context
     */
    public static function setContext($context) {
        self::$context = $context;
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
    public static function setMetaDataFunction($metaDataFunction) {
        self::$metaDataFunction = $metaDataFunction;
    }

    /**
     * Set Bugsnag's error reporting level.
     * If this is not set, we'll use your current PHP error_reporting value
     * from your ini file or error_reporting(...) calls.
     *
     * @param Integer $errorReportingLevel the error reporting level integer
     *                exactly as you would pass to PHP's error_reporting
     */
    public static function setErrorReportingLevel($errorReportingLevel) {
        self::$errorReportingLevel = $errorReportingLevel;
    }

    /**
     * Notify Bugsnag of a non-fatal/handled exception
     *
     * @param Exception $exception the exception to notify Bugsnag about
     * @param Array $metaData optional metaData to send with this error
     */
    public static function notifyException($exception, $metaData=null) {
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
    public static function notifyError($errorName, $errorMessage, $metaData=null) {
        // Get the stack, remove the current function, build a sensible stacktrace]
        $backtrace = debug_backtrace();
        $firstFrame = array_shift($backtrace);
        $stacktrace = self::buildStacktrace($firstFrame["file"], $firstFrame["line"], $backtrace);

        // Send the notification to bugsnag
        self::notify($errorName, $errorMessage, $stacktrace, $metaData);
    }



    // Exception handler callback, should only be called internally by PHP's set_exception_handler
    public static function exceptionHandler($exception) {
        self::notifyException($exception);
    }

    // Exception handler callback, should only be called internally by PHP's set_error_handler
    public static function errorHandler($errno, $errstr, $errfile='', $errline=0, $errcontext=array()) {
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
    public static function fatalErrorHandler() {
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
    private static function notify($errorName, $errorMessage, $stacktrace=null, $passedMetaData=null) {
        // Check if we should notify
        if(is_array(self::$notifyReleaseStages) && !in_array(self::$releaseStage, self::$notifyReleaseStages)) {
            return;
        }

        // Check we have at least an api_key
        if(!isset(self::$apiKey)) {
            error_log('Bugsnag Warning: No API key configured, couldn\'t notify');
            return;
        }

        // Build the error payload to send to Bugsnag
        $error = array(
            'userId' => self::getUserId(),
            'releaseStage' => self::$releaseStage,
            'context' => self::getContext(),
            'exceptions' => array(array(
                'errorClass' => $errorName,
                'message' => $errorMessage,
                'stacktrace' => $stacktrace
            )),
            'metaData' => self::getMetaData($passedMetaData)
        );

        // Add this error payload to the send queue
        self::$errorQueue[] = $error;

        // Flush the queue immediately unless we are batching errors
        if(!self::sendErrorsOnShutdown()) {
            self::flushErrorQueue();
        }
    }

    private static function sendErrorsOnShutdown() {
        return self::isRequest();
    }

    private static function flushErrorQueue() {
        // Post the request to bugsnag
        $statusCode = self::postJSON(self::getEndpoint(), array(
            'apiKey' => self::$apiKey,
            'notifier' => self::$NOTIFIER,
            'events' => self::$errorQueue
        ));

        // Clear the error queue
        self::$errorQueue = array();
    }

    private static function buildStacktrace($topFile, $topLine, $backtrace=null) {
        $stacktrace = array();

        if(!is_null($backtrace)) {
            // PHP backtrace's are misaligned, we need to shift the file/line down a frame
            foreach ($backtrace as $line) {
                $stacktrace[] = self::buildStacktraceFrame($topFile, $topLine, $line['function']);

                $topFile = $line['file'];
                $topLine = $line['line'];
            }

            // Add a final stackframe for the "main" method
            $stacktrace[] = self::buildStacktraceFrame($topFile, $topLine, '[main]');
        } else {
            // No backtrace given, show what we know
            $stacktrace[] = self::buildStacktraceFrame($topFile, $topLine, '[unknown]');
        }

        return $stacktrace;
    }

    private static function buildStacktraceFrame($file, $line, $method) {
        // Check if this frame is inProject
        $inProject = !is_null(self::$projectRoot) && preg_match(self::$projectRootRegex, $file);

        // Strip out projectRoot from start of file path
        if($inProject) {
            $file = preg_replace(self::$projectRootRegex, '', $file);
        }

        // Construct and return the frame
        return array(
            'file' => $file,
            'lineNumber' => $line,
            'method' => $method,
            'inProject' => $inProject
        );
    }

    private static function postJSON($url, $data) {
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

    private static function getEndpoint() {
        return self::$useSSL ? 'https://'.self::$endpoint : 'http://'.self::$endpoint;
    }

    private static function isRequest() {
        return isset($_SERVER['REQUEST_METHOD']);
    }

    private static function getMetaData($passedMetaData=array()) {
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
        if(isset(self::$metaDataFunction) && is_callable(self::$metaDataFunction)) {
            $customMetaData = call_user_func(self::$metaDataFunction);
            if(!is_null($customMetaData) && is_array($customMetaData)) {
                $metaData = array_merge_recursive($metaData, $customMetaData);
            }
        }

        // Merge $passedMetaData
        if(!empty($passedMetaData)) {
            $metaData = array_merge_recursive($metaData, $passedMetaData);
        }

        // Filter metaData according to self::$filters
        $metaData = self::applyFilters($metaData);

        return $metaData;
    }

    private static function getRequestData() {
        $requestData = array();

        // Request Tab
        $requestData['request'] = array();
        $requestData['request']['url'] = self::getCurrentUrl();
        $requestData['request']['httpMethod'] = $_SERVER['REQUEST_METHOD'];
        if(!empty($_POST)) {
            $requestData['request']['params'] = $_POST;
        }
        $requestData['request']['ip'] = self::getRequestIp();
        $requestData['request']['userAgent'] = $_SERVER['HTTP_USER_AGENT'];

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

    private static function getCurrentUrl() {
        $schema = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';

        return $schema.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    private static function getRequestIp() {
        return isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
    }

    private static function getContext() {
        if(self::$context) {
            return self::$context;
        } elseif(self::isRequest()) {
            return $_SERVER['REQUEST_METHOD'] . ' ' . strtok($_SERVER["REQUEST_URI"], '?');
        } else {
            return null;
        }
    }

    private static function getUserId() {
        if(self::$userId) {
            return self::$userId;
        } elseif(self::isRequest()) {
            return self::getRequestIp();
        } else {
            return null;
        }
    }

    private static function applyFilters($metaData) {
        if(!empty(self::$filters)) {
            $cleanMetaData = array();

            foreach ($metaData as $key => $value) {
                $shouldFilter = false;
                foreach(self::$filters as $filter) {
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

    private static function shouldNotify($errno) {
        if(isset(self::$errorReportingLevel)) {
            return self::$errorReportingLevel & $errno;
        } else {
            return error_reporting() & $errno;
        }
    }
}

?>