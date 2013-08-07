<?php namespace Bugsnag;

class Error {
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

    public $name;
    public $message;
    public $stacktrace;
    public $metaData;
    public $config;

    public static function fromPHPException($config, $exception) {
        $error = new Error($config, get_class($exception), $exception->getMessage());
        $error->buildStacktrace($exception->getFile(), $exception->getLine(), $exception->getTrace());

        return $error;
    }

    public static function fromPHPError($config, $code, $message, $file, $line, $backtrace) {
        $error = new Error($config, self::$ERROR_NAMES[$code], $message);
        $error->buildStacktrace($file, $line, $backtrace);

        return $error;
    }

    public function __construct($config, $name, $message, $passedMetaData=null) {
        $this->config = $config;
        $this->name = $name;
        $this->message = $message;

        // Merge metadata from user metadata function
        if(isset($this->config->metaDataFunction) && is_callable($this->config->metaDataFunction)) {
            $this->metaData = call_user_func($this->config->metaDataFunction);
        } else {
            $this->metaData = array();
        }

        // passed metadata
        if(!is_null($passedMetaData) && is_array($passedMetaData)) {
            $this->metaData = array_merge_recursive($this->metaData, $passedMetaData);
        }
    }

    public function toArray() {
        return array(
            // 'userId' => self::getUserId(),
            // 'releaseStage' => $this->releaseStage,
            // 'context' => self::getContext(),
            'exceptions' => array(array(
                'errorClass' => $this->name,
                'message' => $this->message,
                'stacktrace' => $this->stacktrace
            )),
            'metaData' => $this->metaData
        );
    }

    private function buildStacktrace($topFile, $topLine, $backtrace=null) {
        $this->stacktrace = array();

        if(!is_null($backtrace)) {
            // PHP backtrace's are misaligned, we need to shift the file/line down a frame
            foreach ($backtrace as $line) {
                $this->stacktrace[] = $this->buildStacktraceFrame($topFile, $topLine, $line['function']);

                if(isset($line['file']) && isset($line['line'])) {
                    $topFile = $line['file'];
                    $topLine = $line['line'];
                } else {
                    $topFile = "[internal]";
                    $topLine = 0;
                }
            }

            // Add a final stackframe for the "main" method
            $this->stacktrace[] = $this->buildStacktraceFrame($topFile, $topLine, '[main]');
        } else {
            // No backtrace given, show what we know
            $this->stacktrace[] = $this->buildStacktraceFrame($topFile, $topLine, '[unknown]');
        }
    }

    private function buildStacktraceFrame($file, $line, $method) {
        // Check if this frame is inProject
        $inProject = !is_null($this->config->projectRoot) && preg_match($this->config->projectRootRegex, $file);

        // Strip out projectRoot from start of file path
        if($inProject) {
            $file = preg_replace($this->config->projectRootRegex, '', $file);
        }

        // Construct and return the frame
        return array(
            'file' => $file,
            'lineNumber' => $line,
            'method' => $method,
            'inProject' => $inProject
        );
    }
}

?>