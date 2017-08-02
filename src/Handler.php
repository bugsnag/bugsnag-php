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
     * The previously registered error handler as returned by the interpreter.
     *
     * @var callable|null
     */
    protected $previousErrorHandler;

    /**
     * The previously registered exception handler as returned by the interpreter.
     *
     * @var callable|null
     */
    protected $previousExceptionHandler;

    /**
     * Register our exception handler.
     *
     * @param \Bugsnag\Client|string|null $client client instance or api key
     * @param bool $callPrevious whether or not to call the previous handlers
     *
     * @return static
     */
    public static function register($client = null, $callPrevious = true)
    {
        $handler = new static($client instanceof Client ? $client : Client::make($client));

        $handler->registerErrorHandler($callPrevious);
        $handler->registerExceptionHandler($callPrevious);
        $handler->registerShutdownHandler();

        return $handler;
    }

    /**
     * Register the bugsnag error handler and save the previous one
     * (if it exists) to call later.
     *
     * @param bool $callPrevious whether or not to call the previous handler
     *
     * @return void
     */
    public function registerErrorHandler($callPrevious = true)
    {
        $previous = set_error_handler([$this, 'errorHandler']);

        if ($callPrevious) {
            $this->previousErrorHandler = $previous;
        }
    }

    /**
     * Register the bugsnag exception handler and save the previous one
     * (if it exists) to call later.
     *
     * @param bool $callPrevious whether or not to call the previous handler
     *
     * @return void
     */
    public function registerExceptionHandler($callPrevious = true)
    {
        $previous = set_exception_handler([$this, 'exceptionHandler']);

        if ($callPrevious) {
            $this->previousExceptionHandler = $previous;
        }
    }

    /**
     * Register our shutdown handler.
     *
     * PHP will call shutdown functions in the order they were registered.
     *
     * @return void
     */
    public function registerShutdownHandler()
    {
        register_shutdown_function([$this, 'shutdownHandler']);
    }

    /**
     * Create a new exception handler instance.
     *
     * @param \Bugsnag\Client $client
     *
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Exception handler callback.
     *
     * @param \Throwable $throwable the exception was was thrown
     *
     * @return void
     */
    public function exceptionHandler($throwable)
    {
        $report = Report::fromPHPThrowable($this->client->getConfig(), $throwable);

        $report->setSeverity('error');

        $this->client->notify($report);

        if ($this->previousExceptionHandler) {
            call_user_func(
                $this->previousExceptionHandler,
                $throwable
            );
        }
    }

    /**
     * Error handler callback.
     *
     * @param int    $errno   the level of the error raised
     * @param string $errstr  the error message
     * @param string $errfile the filename that the error was raised in
     * @param int    $errline the line number the error was raised at
     *
     * @return bool
     */
    public function errorHandler($errno, $errstr, $errfile = '', $errline = 0)
    {
        if (!$this->client->shouldIgnoreErrorCode($errno)) {
            $report = Report::fromPHPError($this->client->getConfig(), $errno, $errstr, $errfile, $errline);

            $this->client->notify($report);
        }

        if ($this->previousErrorHandler) {
            call_user_func(
                $this->previousErrorHandler,
                $errno,
                $errstr,
                $errfile,
                $errline
            );
        } else {
            return false;
        }
    }

    /**
     * Shutdown handler callback.
     *
     * @return void
     */
    public function shutdownHandler()
    {
        // Get last error
        $lastError = error_get_last();

        // Check if a fatal error caused this shutdown
        if (!is_null($lastError) && ErrorTypes::isFatal($lastError['type']) && !$this->client->shouldIgnoreErrorCode($lastError['type'])) {
            $report = Report::fromPHPError($this->client->getConfig(), $lastError['type'], $lastError['message'], $lastError['file'], $lastError['line'], true);
            $report->setSeverity('error');
            $this->client->notify($report);
        }

        // Flush any buffered errors
        $this->client->flush();
    }
}
