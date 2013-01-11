<?php

// TODO: Static accessors
class Bugsnag {
    public static $instance;

    public static function register($apiKey) {
        $instance = new BugsnagClient($apiKey);
    }
}

// TODO: 
//  Request tab
//  Session tab

class BugsnagClient {
    private static $NOTIFIER = array(
        "name" => "Bugsnag PHP (Official)",
        "version" => "1.0.0",
        "url" => "https://bugsnag.com"
    );

    private static $ERROR_NAMES = array (
        \E_ERROR             => "Fatal Error",
        \E_PARSE             => "Parse Error",
        \E_COMPILE_ERROR     => "Compile Error",
        \E_CORE_ERROR        => "Core Error",
        \E_USER_ERROR        => "User Error",
        \E_NOTICE            => "Notice",
        \E_STRICT            => "Strict",
        \E_USER_WARNING      => "User Warning",
        \E_USER_NOTICE       => "User Notice",
        \E_DEPRECATED        => "Deprecated",
        \E_WARNING           => "Warning",
        \E_USER_DEPRECATED   => "User Deprecated",
        \E_CORE_WARNING      => "Core Warning",
        \E_COMPILE_WARNING   => "Compile Warning",
        \E_RECOVERABLE_ERROR => "Recoverable Error"
    );

    private $apiKey;
    private $releaseStage;
    private $notifyReleaseStages = array("production");
    private $autoNotify = true;
    private $useSSL = false;
    private $projectRoot;
    private $filters = array("password");
    private $endpoint = "notify.bugsnag.com";
    private $originalErrorHandler;
    private $originalExceptionHandler;


    public function __construct($apiKey) {
        $this->apiKey = $apiKey;

        // Register a shutdown function to check for fatal errors
        register_shutdown_function(array($this, "__onShutdown"));

        // Set an error handler for non-fatal errors
        $this->originalErrorHandler = set_error_handler(array($this, "__onError"));
        $this->originalExceptionHandler = set_exception_handler(array($this, "__onException"));
    }

    public function setReleaseStage($releaseStage) {
        $this->releaseStage = $releaseStage;
        return $this;
    }

    public function setNotifyReleaseStages($notifyReleaseStages) {
        $this->notifyReleaseStages = $notifyReleaseStages;
        return $this;
    }
    
    public function setAutoNotify($autoNotify) {
        $this->autoNotify = $autoNotify;
        return $this;
    }

    public function setUseSSL($useSSL) {
        $this->useSSL = $useSSL;
        return $this;
    }

    public function setProjectRoot($projectRoot) {
        $this->projectRoot = $projectRoot;
        return $this;
    }
    
    public function setFilters($filters) {
        $this->filters = $filters;
        return $this;
    }

    public function notify($errorClass, $message, $file, $line, $backtrace=null, $metaData=null) {
        // Check we have at least an api_key
        if(!isset($this->apiKey)) {
            error_log("Bugsnag Warning: No API key configured, couldn't notify");
            return;
        }

        // Build a normalized bugsnag-friendly stacktrace
        $stacktrace = $this->buildStacktrace($file, $line, $backtrace);

        // Post the request to bugsnag
        $statusCode = $this->postJSON($this->getEndpoint(), array(
            "apiKey" => $this->apiKey,
            "notifier" => self::$NOTIFIER,
            "events" => array(array(
                "releaseStage" => $this->releaseStage,
                "exceptions" => array(array(
                    "errorClass" => $errorClass,
                    "message" => $message,
                    "stacktrace" => $stacktrace
                )),
                "metaData" => $metaData
            ))
        ));
    }


    // Don't call this directly! Should only be called as a set_exception_handler callback
    public function __onException($exception) {
        // Send the notification to bugsnag
        $this->notify(
            get_class($exception), 
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTrace()
        );

        // Call the original exception handler
        if($this->originalExceptionHandler && function_exists($this->originalExceptionHandler)) {
            call_user_func($this->originalExceptionHandler, $exception);
        }

        return false;
    }

    // Don't call this directly! Should only be called as a set_error_handler callback
    public function __onError($errno, $errstr, $errfile=null, $errline=null, $errcontext=null) {
        // Get the stack, remove the current function
        $backtrace = debug_backtrace();
        array_shift($backtrace);

        // Send the notification to bugsnag
        $this->notify(
            self::$ERROR_NAMES[$errno],
            $errstr,
            $errfile,
            $errline,
            $backtrace
        );

        // Call the original error handler
        if($this->originalErrorHandler && function_exists($this->originalErrorHandler)) {
            call_user_func($this->originalErrorHandler, $errno, $errstr, $errfile, $errline, $errcontext);
        }
        
        return false;
    }

    // Don't call this directly! Should only be called as a register_shutdown_function callback
    public function __onShutdown() {
        $lastError = error_get_last();

        // Check if a fatal error caused this shutdown
        if($lastError && $lastError["type"] === 1 || $lastError["type"] === 4 || $lastError["type"] === 64) {
            // NOTE: We can't get the error's backtrace here :(
            // Send the notification to bugsnag
            $this->notify(
                self::$ERROR_NAMES[$lastError["type"]],
                $lastError["message"],
                $lastError["file"],
                $lastError["line"]
            );
        }
    }


    private function buildStacktrace($topFile, $topLine, $backtrace=null) {
        $stacktrace = array();

        if($backtrace) {
            foreach ($backtrace as $line) {
                array_push($stacktrace, array(
                    "file" => $this->removeProjectRoot($topFile),
                    "lineNumber" => $topLine,
                    "method" => $line["function"]
                ));

                $topFile = $line["file"];
                $topLine = $line["line"];
            }
        
            array_push($stacktrace, array(
                "file" => $this->removeProjectRoot($topFile),
                "lineNumber" => $topLine,
                "method" => "[main]"
            ));
        } else {
            array_push($stacktrace, array(
                "file" => $this->removeProjectRoot($topFile),
                "lineNumber" => $topLine,
                "method" => "[unknown]"
            ));
        }

        return $stacktrace;
    }

    private function postJSON($url, $data) {
        $http = curl_init($url);
    
        curl_setopt($http, CURLOPT_HEADER, false);
        curl_setopt($http, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($http, CURLOPT_POST, true);
        curl_setopt($http, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($http, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($http, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($http, CURLOPT_SSL_VERIFYPEER, FALSE);

        $responseBody = curl_exec($http);
        $statusCode = curl_getinfo($http, CURLINFO_HTTP_CODE);

        if($statusCode > 200) {
            error_log("Bugsnag Warning: Couldn't notify (".$responseBody.")");
        }

        curl_close($http);

        return $statusCode;
    }
    
    private function getEndpoint() {
        return $this->useSSL ? "https://".$this->endpoint : "http://".$this->endpoint;
    }
    
    private function removeProjectRoot($path) {
        $filePath = str_replace( '\\', '/', $path );

        if(!$this->projectRoot) {
            return $filePath;
        }

        if(strpos($filePath, $this->projectRoot) === 0 && strlen($this->projectRoot) < strlen($filePath)) {
            return substr($filePath, strlen($this->projectRoot)+1);
        } else {
            return $filePath;
        }
    }
}

?>