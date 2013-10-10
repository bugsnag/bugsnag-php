<?php

class Bugsnag_Stacktrace {
    private $frames;
    private $config;

    public function __construct($config, $topFile=null, $topLine=null, $backtrace=null, $generateBacktrace=true) {
        $this->config = $config;

        if($generateBacktrace) {
            // Generate a new backtrace unless we were given one
            if(is_null($backtrace)) {
                $backtrace = debug_backtrace();
            }

            // Throw away any stackframes that occurred in Bugsnag code
            $lastFrame = null;
            while(!empty($backtrace) && isset($backtrace[0]["class"]) && strpos($backtrace[0]["class"], "Bugsnag_") == 0) {
              $lastFrame = array_shift($backtrace);
            }

            // If we weren't passed a topFile and topLine, use the values from lastFrame
            if(is_null($topFile) && is_null($topLine)) {
                $topFile = $lastFrame['file'];
                $topLine = $lastFrame['line'];
            }

            // PHP backtrace's are misaligned, we need to shift the file/line down a frame
            foreach($backtrace as $line) {
                $this->frames[] = $this->buildFrame($topFile, $topLine, $line['function']);

                if(isset($line['file']) && isset($line['line'])) {
                    $topFile = $line['file'];
                    $topLine = $line['line'];
                } else {
                    $topFile = "[internal]";
                    $topLine = 0;
                }
            }

            // Add a final stackframe for the "main" method
            $this->frames[] = $this->buildFrame($topFile, $topLine, '[main]');
        } else {
            $this->frames[] = $this->buildFrame($topFile, $topLine, '[unknown]');
        }
    }

    public function toArray() {
        return $this->frames;
    }

    private function buildFrame($file, $line, $method) {
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