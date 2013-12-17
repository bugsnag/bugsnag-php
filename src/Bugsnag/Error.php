<?php

class Bugsnag_Error
{
    private static $VALID_SEVERITIES = array(
        'fatal',
        'error',
        'warning',
        'info'
    );

    private static $ERROR_TYPES = array(
        E_ERROR => array(
            'name' => 'PHP Fatal Error',
            'severity' => 'fatal'
        ),

        E_WARNING => array(
            'name' => 'PHP Warning',
            'severity' => 'warning'
        ),

        E_PARSE => array(
            'name' => 'PHP Parse Error',
            'severity' => 'fatal'
        ),

        E_NOTICE => array(
            'name' => 'PHP Notice',
            'severity' => 'info'
        ),

        E_CORE_ERROR => array(
            'name' => 'PHP Core Error',
            'severity' => 'fatal'
        ),

        E_CORE_WARNING => array(
            'name' => 'PHP Core Warning',
            'severity' => 'warning'
        ),

        E_COMPILE_ERROR => array(
            'name' => 'PHP Compile Error',
            'severity' => 'fatal'
        ),

        E_COMPILE_WARNING => array(
            'name' => 'PHP Compile Warning',
            'severity' => 'warning'
        ),

        E_USER_ERROR => array(
            'name' => 'User Error',
            'severity' => 'error'
        ),

        E_USER_WARNING => array(
            'name' => 'User Warning',
            'severity' => 'warning'
        ),

        E_USER_NOTICE => array(
            'name' => 'User Notice',
            'severity' => 'info'
        ),

        E_STRICT => array(
            'name' => 'PHP Strict',
            'severity' => 'info'
        ),

        E_RECOVERABLE_ERROR => array(
            'name' => 'PHP Recoverable Error',
            'severity' => 'error'
        ),

        // E_DEPRECATED (Since PHP 5.3.0)
        8192 => array(
            'name' => 'PHP Deprecated',
            'severity' => 'info'
        ),

        // E_USER_DEPRECATED (Since PHP 5.3.0)
        16384 => array(
            'name' => 'User Deprecated',
            'severity' => 'info'
        )
    );

    public static function isFatal($code)
    {
        return array_key_exists($code, self::$ERROR_TYPES) && self::$ERROR_TYPES[$code]['severity'] == 'fatal';
    }

    public static function getName($code)
    {
        if(array_key_exists($code, self::$ERROR_TYPES)) {
            return self::$ERROR_TYPES[$code]['name'];
        } else {
            return "Unknown";
        }
    }

    public static function getSeverity($code)
    {
        if(array_key_exists($code, self::$ERROR_TYPES)) {
            return self::$ERROR_TYPES[$code]['severity'];
        } else {
            return "error";
        }
    }

    public $name;
    public $message;
    public $severity = "error";
    public $stacktrace;
    public $metaData = array();
    public $config;
    public $diagnostics;
    public $code;

    public function __construct(Bugsnag_Configuration $config, Bugsnag_Diagnostics $diagnostics)
    {
        $this->config = $config;
        $this->diagnostics = $diagnostics;
        $this->stacktrace = new Bugsnag_Stacktrace($this->config);
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function setSeverity($severity)
    {
        if(!is_null($severity)) {
            if(in_array($severity, Bugsnag_Error::$VALID_SEVERITIES)) {
                $this->severity = $severity;
            } else {
                error_log('Bugsnag Warning: Tried to set error severity to '. $severity .' which is not allowed.');
            }
        }

        return $this;
    }

    public function setPHPException(Exception $exception)
    {
        $this->setName(get_class($exception));
        $this->setMessage($exception->getMessage());
        $this->stacktrace = new Bugsnag_Stacktrace($this->config, $exception->getFile(), $exception->getLine(), $exception->getTrace());

        return $this;
    }

    public function setPHPError($code, $message, $file, $line, $fatal=false)
    {
        $this->setName(Bugsnag_Error::getName($code));
        $this->setMessage($message);
        $this->setSeverity(Bugsnag_Error::getSeverity($code));

        $this->stacktrace = new Bugsnag_Stacktrace($this->config, $file, $line, NULL, $fatal);
        $this->code = $code;

        return $this;
    }

    public function setMetaData($metaData)
    {
        if (is_array($metaData)) {
            $this->metaData = array_merge_recursive($this->metaData, $metaData);
        }

        return $this;
    }

    public function shouldIgnore()
    {
        // Check if we should ignore errors of this type
        if (isset($this->code)) {
            if (isset($this->config->errorReportingLevel)) {
                return !($this->config->errorReportingLevel & $this->code);
            } else {
                return !(error_reporting() & $this->code);
            }
        }

        return false;
    }

    public function toArray()
    {
        return array(
            'app' => $this->diagnostics->getAppData(),
            'device' => $this->diagnostics->getDeviceData(),
            'user' => $this->diagnostics->getUser(),
            'context' => $this->diagnostics->getContext(),
            'severity' => $this->severity,
            'exceptions' => array(array(
                'errorClass' => $this->name,
                'message' => $this->message,
                'stacktrace' => $this->stacktrace->toArray()
            )),
            'metaData' => $this->applyFilters($this->metaData)
        );
    }

    private function applyFilters($metaData)
    {
        if (!empty($this->config->filters)) {
            $cleanMetaData = array();

            foreach ($metaData as $key => $value) {
                $shouldFilter = false;
                foreach ($this->config->filters as $filter) {
                    if (strpos($key, $filter) !== false) {
                        $shouldFilter = true;
                        break;
                    }
                }

                if ($shouldFilter) {
                    $cleanMetaData[$key] = '[FILTERED]';
                } else {
                    if (is_array($value)) {
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
