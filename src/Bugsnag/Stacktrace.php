<?php

class Bugsnag_Stacktrace
{
    public $frames = array();
    private $config;

    public static function generate($config)
    {
        // Reduce memory usage by omitting args and objects from backtrace
        if (version_compare(PHP_VERSION, '5.3.6') >= 0) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS & ~DEBUG_BACKTRACE_PROVIDE_OBJECT);
        } elseif (version_compare(PHP_VERSION, '5.2.5') >= 0) {
            $backtrace = debug_backtrace(FALSE);
        } else {
            $backtrace = debug_backtrace();
        }

        return self::fromBacktrace($config, $backtrace, "[generator]", 0);
    }

    public static function fromFrame($config, $file, $line)
    {
        $stacktrace = new Bugsnag_Stacktrace($config);
        $stacktrace->addFrame($file, $line, "[unknown]");

        return $stacktrace;
    }

    public static function fromBacktrace($config, $backtrace, $topFile, $topLine)
    {
        $stacktrace = new Bugsnag_Stacktrace($config);

        // PHP backtrace's are misaligned, we need to shift the file/line down a frame
        foreach ($backtrace as $frame) {
            if (!self::frameInsideBugsnag($frame)) {
                $stacktrace->addFrame($topFile, $topLine, $frame['function'], isset($frame['class']) ? $frame['class'] : NULL);
            }

            if (isset($frame['file']) && isset($frame['line'])) {
                $topFile = $frame['file'];
                $topLine = $frame['line'];
            } else {
                $topFile = "[internal]";
                $topLine = 0;
            }
        }

        // Add a final stackframe for the "main" method
        $stacktrace->addFrame($topFile, $topLine, '[main]');

        return $stacktrace;
    }

    public static function frameInsideBugsnag($frame)
    {
        return isset($frame['class']) && strpos($frame['class'], 'Bugsnag_') === 0;
    }


    public function __construct($config)
    {
        $this->config = $config;
    }

    public function toArray()
    {
        return $this->frames;
    }

    public function addFrame($file, $line, $method, $class=NULL)
    {
        // Check if this frame is inProject
        $inProject = !is_null($this->config->projectRootRegex) && preg_match($this->config->projectRootRegex, $file);

        // Strip out projectRoot from start of file path
        if ($inProject) {
            $file = preg_replace($this->config->projectRootRegex, '', $file);
        }

        // Construct the frame
        $frame = array(
            'file' => $file,
            'lineNumber' => $line,
            'method' => $method,
            'inProject' => $inProject
        );

        if (!empty($class)) {
            $frame['class'] = $class;
        }

        $this->frames[] = $frame;
    }
}
