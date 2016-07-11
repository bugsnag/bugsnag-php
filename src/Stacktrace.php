<?php

namespace Bugsnag;

use RuntimeException;
use SplFileObject;

class Stacktrace
{
    /**
     * The default number of lines of code to include.
     *
     * @var int
     */
    const NUM_LINES = 7;

    /**
     * The default maximum line length for included code.
     *
     * @var int
     */
    const MAX_LENGTH = 200;

    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    protected $config;

    /**
     * The array of frames.
     *
     * @var array
     */
    protected $frames = [];

    /**
     * Generate a new stacktrace using the given config.
     *
     * @param \Bugsnag\Configuration $config the configuration instance
     *
     * @return static
     */
    public static function generate(Configuration $config)
    {
        // Reduce memory usage by omitting args and objects from backtrace
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS & ~DEBUG_BACKTRACE_PROVIDE_OBJECT);

        return static::fromBacktrace($config, $backtrace, '[generator]', 0);
    }

    /**
     * Create a new stacktrace instance from a frame.
     *
     * @param \Bugsnag\Configuration $config the configuration instance
     * @param string                 $file   the associated file
     * @param int                    $line   the line number
     *
     * @return static
     */
    public static function fromFrame(Configuration $config, $file, $line)
    {
        $stacktrace = new static($config);
        $stacktrace->addFrame($file, $line, '[unknown]');

        return $stacktrace;
    }

    /**
     * Create a new stacktrace instance from a backtrace.
     *
     * @param \Bugsnag\Configuration $config    the configuration instance
     * @param array                  $backtrace the associated backtrace
     * @param int                    $topFile   the top file to use
     * @param int                    $topLine   the top line to use
     *
     * @return static
     */
    public static function fromBacktrace(Configuration $config, array $backtrace, $topFile, $topLine)
    {
        $stacktrace = new static($config);

        // PHP backtrace's are misaligned, we need to shift the file/line down a frame
        foreach ($backtrace as $frame) {
            if (!static::frameInsideBugsnag($frame)) {
                $stacktrace->addFrame(
                    $topFile,
                    $topLine,
                    isset($frame['function']) ? $frame['function'] : null,
                    isset($frame['class']) ? $frame['class'] : null
                );
            }

            if (isset($frame['file']) && isset($frame['line'])) {
                $topFile = $frame['file'];
                $topLine = $frame['line'];
            } else {
                $topFile = '[internal]';
                $topLine = 0;
            }
        }

        // Add a final stackframe for the "main" method
        $stacktrace->addFrame($topFile, $topLine, '[main]');

        return $stacktrace;
    }

    /**
     * Does the given frame internally belong to bugsnag.
     *
     * @param array $frame the given frame to check
     *
     * @return bool
     */
    public static function frameInsideBugsnag(array $frame)
    {
        return isset($frame['class']) && strpos($frame['class'], 'Bugsnag\\') === 0 && substr_count($frame['class'], '\\') === 1;
    }

    /**
     * Create a new stacktrace instance.
     *
     * @param \Bugsnag\Configuration $config the configuration instance
     *
     * @return void
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Get the array representation.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->frames;
    }

    /**
     * Add the given frame to the stacktrace.
     *
     * @param string      $file   the associated file
     * @param int         $line   the line number
     * @param string      $method the method called
     * @param string|null $class  the associated class
     *
     * @return void
     */
    public function addFrame($file, $line, $method, $class = null)
    {
        // Account for special "filenames" in eval'd code
        $matches = [];
        if (preg_match("/^(.*?)\((\d+)\) : (?:eval\(\)'d code|runtime-created function)$/", $file, $matches)) {
            $file = $matches[1];
            $line = $matches[2];
        }

        // Construct the frame
        $frame = [
            'lineNumber' => (int) $line,
            'method' => $class ? "$class::$method" : $method,
        ];

        // Attach some lines of code for context
        if ($this->config->shouldSendCode()) {
            $frame['code'] = $this->getCode($file, $line, static::NUM_LINES);
        }

        // Check if this frame is inProject
        $frame['inProject'] = $this->config->isInProject($file);

        // Strip out projectRoot from start of file path
        $frame['file'] = $this->config->getStrippedFilePath($file);

        $this->frames[] = $frame;
    }

    /**
     * Extract the code for the given file and lines.
     *
     * @param string $path     the path to the file
     * @param int    $line     the line to centre about
     * @param string $numLines the number of lines to fetch
     *
     * @return string[]|null
     */
    protected function getCode($path, $line, $numLines)
    {
        if (empty($path) || empty($line) || !file_exists($path)) {
            return;
        }

        try {
            $file = new SplFileObject($path);
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key() + 1;

            $bounds = Parser::getBounds($line, $numLines, $totalLines);

            $code = [];

            $file->seek($bounds[0] - 1);
            while ($file->key() < $bounds[1]) {
                $code[$file->key() + 1] = rtrim(substr($file->current(), 0, static::MAX_LENGTH));
                $file->next();
            }

            return $code;
        } catch (RuntimeException $ex) {
            // do nothing
        }
    }
}
