<?php

class Bugsnag_Error {
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

    public static $FATAL_ERRORS = array(
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING,
        E_STRICT
    );

    public $name;
    public $message;
    public $stacktrace;
    public $metaData;
    public $config;
    public $code;

    public $userId;
    public $context;

    public static function fromPHPException($config, $exception) {
        $error = new self($config, get_class($exception), $exception->getMessage());
        $error->stacktrace = new Bugsnag_Stacktrace($config, $exception->getFile(), $exception->getLine(), $exception->getTrace());

        return $error;
    }

    public static function fromPHPError($config, $code, $message, $file, $line) {
        $error = new self($config, self::$ERROR_NAMES[$code], $message);
        $error->stacktrace = new Bugsnag_Stacktrace($config, $file, $line);
        $error->code = $code;

        return $error;
    }

    public static function fromPHPFatalError($config, $code, $message, $file, $line) {
        $error = new self($config, self::$ERROR_NAMES[$code], $message);
        $error->stacktrace = new Bugsnag_Stacktrace($config, $file, $line, null, false);
        $error->code = $code;

        return $error;
    }

    public static function fromNamedError($config, $name, $message) {
        $error = new self($config, $name, $message);
        $error->stacktrace = new Bugsnag_Stacktrace($config);

        return $error;
    }

    private function __construct($config, $name, $message) {
        $this->config = $config;
        $this->name = $name;
        $this->message = $message;
        $this->metaData = array();

        // Merge custom metadata
        $this->setMetaData($this->config->metaData);
    }

    public function setMetaData($metaData) {
        if(is_array($metaData)) {
            $this->metaData = array_merge_recursive($this->metaData, $metaData);
        }
    }

    public function getContext() {
        return $this->config->context ? $this->config->context : Bugsnag_Request::getContext();
    }

    public function getUserId() {
        return $this->config->userId ? $this->config->userId : Bugsnag_Request::getUserId();
    }

    public function shouldIgnore() {
        // Check if we should ignore errors of this type
        if(isset($this->code)) {
            if(isset($this->config->errorReportingLevel)) {
                return !($this->config->errorReportingLevel & $this->code);
            } else {
                return !(error_reporting() & $this->code);
            }
        }

        return false;
    }

    public function toArray() {
        // Run beforeNotify function
        if(isset($this->config->beforeNotifyFunction) && is_callable($this->config->beforeNotifyFunction)) {
            $beforeNotifyReturn = call_user_func($this->config->beforeNotifyFunction, $this);
        }

        // Skip this error if the beforeNotify function returned FALSE
        if(isset($beforeNotifyReturn) && $beforeNotifyReturn === FALSE) {
            return NULL;
        }

        // Set up the context and userId
        $this->context = $this->getContext();
        $this->userId = $this->getUserId();

        return array(
            'userId' => $this->userId,
            'releaseStage' => $this->config->releaseStage,
            'context' => $this->context,
            'exceptions' => array(array(
                'errorClass' => $this->name,
                'message' => $this->message,
                'stacktrace' => $this->stacktrace->toArray()
            )),
            'metaData' => $this->applyFilters($this->metaData)
        );
    }

    private function applyFilters($metaData) {
        if(!empty($this->config->filters)) {
            $cleanMetaData = array();

            foreach ($metaData as $key => $value) {
                $shouldFilter = false;
                foreach($this->config->filters as $filter) {
                    if(strpos($key, $filter) !== false) {
                        $shouldFilter = true;
                        break;
                    }
                }

                if($shouldFilter) {
                    $cleanMetaData[$key] = '[FILTERED]';
                } else {
                    if(is_array($value)) {
                        $cleanMetaData[$key] = $this->applyFilters($value);
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