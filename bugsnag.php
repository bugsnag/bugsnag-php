<?php

/**
 * Bugsnag PHP Client
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
        \E_ERROR             => 'Fatal Error',
        \E_PARSE             => 'Parse Error',
        \E_COMPILE_ERROR     => 'Compile Error',
        \E_CORE_ERROR        => 'Core Error',
        \E_USER_ERROR        => 'User Error',
        \E_NOTICE            => 'Notice',
        \E_STRICT            => 'Strict',
        \E_USER_WARNING      => 'User Warning',
        \E_USER_NOTICE       => 'User Notice',
        \E_DEPRECATED        => 'Deprecated',
        \E_WARNING           => 'Warning',
        \E_USER_DEPRECATED   => 'User Deprecated',
        \E_CORE_WARNING      => 'Core Warning',
        \E_COMPILE_WARNING   => 'Compile Warning',
        \E_RECOVERABLE_ERROR => 'Recoverable Error'
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
    private static $metaDataFunction;
    private static $registeredShutdown = false;
    

    /**
     * Initialize Bugsnag
     *
     * @param String $apiKey your Bugsnag API key
     */
    public static function register($apiKey) {
        self::$apiKey = $apiKey;

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
     * The Bugsnag exception handling function to use with PHP's 
     * set_exception_handler method.
     *
     * @param Exception $exception the exception to notify Bugsnag about
     */
    public static function exceptionHandler($exception) {
        // Build a sensible stacktrace
        $stacktrace = self::buildStacktrace($exception->getFile(), $exception->getLine(), $exception->getTrace());

        // Send the notification to bugsnag
        self::notify(get_class($exception), $exception->getMessage(), $stacktrace);
    }

    /**
     * The Bugsnag error handling function to use with PHP's set_error_handler.
     *
     * @param Integer $errno the error code
     * @param String $errstr the error message
     * @param String $errfile the file the error occurred in
     * @param String $errline the line the error occurred on
     * @param String $errcontext unused
     */
    public static function errorHandler($errno, $errstr, $errfile='', $errline=0, $errcontext=array()) {
        // Get the stack, remove the current function, build a sensible stacktrace]
        $backtrace = debug_backtrace();
        // TODO What if this is called from the users errorHandler?
        array_shift($backtrace);
        $stacktrace = self::buildStacktrace($errfile, $errline, $backtrace);

        // Send the notification to bugsnag
        self::notify(self::$ERROR_NAMES[$errno], $errstr, $stacktrace);
    }



    // Private or undocumented methods
    public static function fatalErrorHandler() {
        $lastError = error_get_last();

        // Check if a fatal error caused this shutdown
        if(!is_null($lastError) && in_array($lastError['type'], self::$FATAL_ERRORS)) {
            // NOTE: We can't get the error's backtrace here :(
            $stacktrace = self::buildStacktrace($lastError['file'], $lastError['line']);

            // Send the notification to bugsnag
            self::notify(self::$ERROR_NAMES[$lastError['type']], $lastError['message'], $stacktrace);
        }
    }

    // TODO Add a manual notify method that supports metaData
    private static function notify($errorName, $errorMessage, $stacktrace=null, $metaData=null) {
        // Check if we should notify
        if(is_array(self::$notifyReleaseStages) && !in_array(self::$releaseStage, self::$notifyReleaseStages)) {
            return;
        }

        // Check we have at least an api_key
        if(!isset(self::$apiKey)) {
            error_log('Bugsnag Warning: No API key configured, couldn\'t notify');
            return;
        }

        // TODO userId
        // Post the request to bugsnag
        $statusCode = self::postJSON(self::getEndpoint(), array(
            'apiKey' => self::$apiKey,
            'notifier' => self::$NOTIFIER,
            'events' => array(array(
                'releaseStage' => self::$releaseStage,
                'exceptions' => array(array(
                    'errorClass' => $errorName,
                    'message' => $errorMessage,
                    'stacktrace' => $stacktrace
                )),
                'context' => self::getContext(),
                'metaData' => self::getMetaData()
            ))
        ));
    }

    private static function buildStacktrace($topFile, $topLine, $backtrace=null) {
        $stacktrace = array();

        //TODO split out hash generation
        if(!is_null($backtrace)) {
            foreach ($backtrace as $line) {
                array_push($stacktrace, array(
                    'file' => self::stripProjectRoot($topFile),
                    'lineNumber' => $topLine,
                    'method' => $line['function'],
                    'inProject' => self::isInProject($topFile)
                ));

                $topFile = $line['file'];
                $topLine = $line['line'];
            }

            array_push($stacktrace, array(
                'file' => self::stripProjectRoot($topFile),
                'lineNumber' => $topLine,
                'method' => '[main]',
                'inProject' => self::isInProject($topFile)
            ));
        } else {
            array_push($stacktrace, array(
                'file' => self::stripProjectRoot($topFile),
                'lineNumber' => $topLine,
                'method' => '[unknown]',
                'inProject' => self::isInProject($topFile)
            ));
        }

        return $stacktrace;
    }

    private static function postJSON($url, $data) {
        $http = curl_init($url);

        curl_setopt($http, CURLOPT_HEADER, false);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($http, CURLOPT_POST, true);
        curl_setopt($http, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($http, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($http, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($http, CURLOPT_SSL_VERIFYPEER, FALSE);

        $responseBody = curl_exec($http);
        $statusCode = curl_getinfo($http, CURLINFO_HTTP_CODE);
        
        // TODO Can we make this async or batch them up
        if($statusCode > 200) {
            error_log('Bugsnag Warning: Couldn\'t notify ('.$responseBody.')');
        }

        curl_close($http);

        return $statusCode;
    }

    private static function getEndpoint() {
        return self::$useSSL ? 'https://'.self::$endpoint : 'http://'.self::$endpoint;
    }

    private static function stripProjectRoot($path) {
        if(self::isInProject($path)) {
            return preg_replace(self::projectRootRegex(), '', $path);
        } else {
            return $path;
        }
    }

    private static function isInProject($path) {
        return !is_null(self::$projectRoot) && preg_match(self::projectRootRegex(), $path);
    }

    private static function projectRootRegex() {
        // TODO Cache this as a result of setting projectRoot
        return '/'.preg_quote(self::$projectRoot, '/')."[\\/]?/i";
    }

    private static function isRequest() {
        return isset($_SERVER['REQUEST_METHOD']);
    }

    private static function getMetaData($passedMetaData=array()) {
        $metaData = array();

        // Add http request info
        if(self::isRequest()) {
            $metaData = array_merge($metaData, self::getRequestData());
        }

        // Add environment info
        if(!empty($_ENV)) {
            $metaData['environment'] = $_ENV;
        }

        // Merge user-defined metadata if custom function is specified
        if(isset(self::$metaDataFunction) && is_callable(self::$metaDataFunction)) {
            $customMetaData = call_user_func(self::$metaDataFunction);
            // TODO What if this is null?
            if(is_array($customMetaData)) {
                // TODO deep merge?
                $metaData = array_merge($metaData, $customMetaData);
            }
        }

        // Merge $passedMetaData
        if(!empty($passedMetaData)) {
            $metaData = array_merge($metaData, $passedMetaData);
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
        $requestData['request']['ip'] = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
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

    private static function getContext() {
        if(self::isRequest()) {
            return $_SERVER['REQUEST_METHOD'] . ' ' . strtok($_SERVER["REQUEST_URI"], '?');
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
}

?>