<?php

namespace Bugsnag;

class Handler
{
    /**
     * The client instance.
     *
     * @var \Bugsnag\Client
     */
    protected $client;

    /**
     * Register our exception handler.
     *
     * @param \Bugsnag\Client $client
     *
     * @return void
     */
    public static function register(Client $client)
    {
        $client = new static($client);

        set_error_handler([$client, 'errorHandler']);
        set_exception_handler([$client, 'exceptionHandler']);
        register_shutdown_function([$client, 'shutdownHandler']);
    }

    /**
     * Create a new exception handler instance.
     *
     * @param \Bugsnag\Client $client
     *
     * @return void
     */
    protected function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Exception handler callback.
     *
     * Should only be called internally by PHP's set_exception_handler.
     *
     * @param \Throwable $throwable the exception was was thrown
     *
     * @return void
     */
    public function exceptionHandler($throwable)
    {
        $error = Error::fromPHPThrowable($this->config, $this->diagnostics, $throwable);

        $error->setSeverity('error');

        $this->notify($error);
    }

    /**
     * Error handler callback.
     *
     * Should only be called internally by PHP's set_error_handler.
     *
     * @param int    $errno   the level of the error raised
     * @param string $errstr  the error message
     * @param string $errfile the filename that the error was raised in
     * @param int    $errline the line number the error was raised at
     *
     * @return void
     */
    public function errorHandler($errno, $errstr, $errfile = '', $errline = 0)
    {
        if ($this->config->shouldIgnoreErrorCode($errno)) {
            return;
        }

        $error = Error::fromPHPError($this->config, $this->diagnostics, $errno, $errstr, $errfile, $errline);
        $this->notify($error);
    }

    /**
     * Shutdown handler callback.
     *
     * Should only be called internally by PHP's register_shutdown_function.
     *
     * @return void
     */
    public function shutdownHandler()
    {
        // Get last error
        $lastError = error_get_last();

        // Check if a fatal error caused this shutdown
        if (!is_null($lastError) && ErrorTypes::isFatal($lastError['type']) && !$this->config->shouldIgnoreErrorCode($lastError['type'])) {
            $error = Error::fromPHPError($this->config, $this->diagnostics, $lastError['type'], $lastError['message'], $lastError['file'], $lastError['line'], true);
            $error->setSeverity('error');
            $this->notify($error);
        }

        // Flush any buffered errors
        if ($this->notification) {
            $this->notification->deliver();
            $this->notification = null;
        }
    }
}
