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
     * @param \Bugsnag\Client|string|null $client client instance or api key
     *
     * @return static
     */
    public static function register($client = null)
    {
        $handler = new static($client instanceof Client ? $client : Client::make($client));

        set_error_handler([$handler, 'errorHandler']);
        set_exception_handler([$handler, 'exceptionHandler']);
        register_shutdown_function([$handler, 'shutdownHandler']);

        return $handler;
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
     * @param \Throwable $throwable the exception was was thrown
     *
     * @return void
     */
    public function exceptionHandler($throwable)
    {
        $report = Report::fromPHPThrowable($this->client->getConfig(), $this->client->getFilesystem(), $throwable);

        $report->setSeverity('error');

        $this->client->notify($report);
    }

    /**
     * Error handler callback.
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
        if ($this->client->shouldIgnoreErrorCode($errno)) {
            return;
        }

        $report = Report::fromPHPError($this->client->getConfig(), $this->client->getFilesystem(), $errno, $errstr, $errfile, $errline);

        $this->client->notify($report);
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
            $report = Report::fromPHPError($this->client->getConfig(), $this->client->getFilesystem(), $lastError['type'], $lastError['message'], $lastError['file'], $lastError['line'], true);
            $report->setSeverity('error');
            $this->client->notify($report);
        }

        // Flush any buffered errors
        $this->client->flush();
    }
}
