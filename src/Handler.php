<?php

namespace Bugsnag;

use Exception;
use Throwable;

class Handler
{
    /**
     * The client instance.
     *
     * @var \Bugsnag\Client
     */
    protected $client;

    /**
     * The previously registered error handler.
     *
     * @var callable|null
     */
    protected $previousErrorHandler;

    /**
     * The previously registered exception handler.
     *
     * @var callable|null
     */
    protected $previousExceptionHandler;

    /**
     * Whether the shutdown handler will run.
     *
     * This is used to disable the shutdown handler in order to avoid double
     * reporting exceptions when trying to run the native PHP exception handler.
     *
     * @var bool
     */
    private static $enableShutdownHandler = true;

    /**
     * Register our handlers.
     *
     * @param \Bugsnag\Client|string|null $client client instance or api key
     *
     * @return static
     */
    public static function register($client = null)
    {
        if (!$client instanceof Client) {
            $client = Client::make($client);
        }

        $handler = new static($client);
        $handler->registerBugsnagHandlers(true);

        return $handler;
    }

    /**
     * Register our handlers and preserve those previously registered.
     *
     * @param \Bugsnag\Client|string|null $client client instance or api key
     *
     * @return static
     *
     * @deprecated Use {@see Handler::register} instead.
     */
    public static function registerWithPrevious($client = null)
    {
        return self::register($client);
    }

    /**
     * Register our handlers, optionally saving those previously registered.
     *
     * @param bool $callPrevious whether or not to call the previous handlers
     *
     * @return void
     */
    protected function registerBugsnagHandlers($callPrevious)
    {
        $this->registerErrorHandler($callPrevious);
        $this->registerExceptionHandler($callPrevious);
        $this->registerShutdownHandler();
    }

    /**
     * Register the bugsnag error handler and save the returned value.
     *
     * @param bool $callPrevious whether or not to call the previous handler
     *
     * @return void
     */
    public function registerErrorHandler($callPrevious)
    {
        $previous = set_error_handler([$this, 'errorHandler']);

        if ($callPrevious) {
            $this->previousErrorHandler = $previous;
        }
    }

    /**
     * Register the bugsnag exception handler and save the returned value.
     *
     * @param bool $callPrevious whether or not to call the previous handler
     *
     * @return void
     */
    public function registerExceptionHandler($callPrevious)
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
        $report = Report::fromPHPThrowable(
            $this->client->getConfig(),
            $throwable
        );

        $report->setSeverity('error');
        $report->setUnhandled(true);
        $report->setSeverityReason([
            'type' => 'unhandledException',
        ]);

        $this->client->notify($report);

        // If we have no previous exception handler to call, there's nothing left
        // to do. This could be because one never existed, or we may have disabled
        // it if it previously raised an exception
        if (!$this->previousExceptionHandler) {
            return;
        }

        $exceptionFromPreviousHandler = null;

        // Get a reference to the previous handler and then disable it — this
        // prevents an infinite loop if the previous handler raises a new exception
        $previousExceptionHandler = $this->previousExceptionHandler;
        $this->previousExceptionHandler = null;

        // These empty catches exist to set $exceptionFromPreviousHandler — we
        // support both PHP 5 & 7 so can't have a single Throwable catch
        try {
            call_user_func($previousExceptionHandler, $throwable);

            return;
        } catch (Throwable $exceptionFromPreviousHandler) {
        } catch (Exception $exceptionFromPreviousHandler) {
        }

        // If the previous handler threw the same exception that we are currently
        // handling then it's trying to force PHP's native exception handler to run
        // In this case we disable our shutdown handler (to avoid reporting it
        // twice) and re-throw the exception
        if ($throwable === $exceptionFromPreviousHandler) {
            self::$enableShutdownHandler = false;

            throw $throwable;
        }

        // The previous handler raised a new exception so try to handle it too
        // We've disabled the previous handler so it can't trigger _another_ exception
        $this->exceptionHandler($exceptionFromPreviousHandler);
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
        if (!$this->client->getConfig()->shouldIgnoreErrorCode($errno)) {
            $report = Report::fromPHPError(
                $this->client->getConfig(),
                $errno,
                $errstr,
                $errfile,
                $errline,
                false
            );

            $report->setUnhandled(true);
            $report->setSeverityReason([
                'type' => 'unhandledError',
                'attributes' => [
                    'errorType' => ErrorTypes::getName($errno),
                ],
            ]);

            $this->client->notify($report);
        }

        if ($this->previousErrorHandler) {
            return call_user_func(
                $this->previousErrorHandler,
                $errno,
                $errstr,
                $errfile,
                $errline
            );
        }

        return false;
    }

    /**
     * Shutdown handler callback.
     *
     * @return void
     */
    public function shutdownHandler()
    {
        // If we're disabled, do nothing. This avoids reporting twice if the
        // exception handler is forcing the native PHP handler to run
        if (!self::$enableShutdownHandler) {
            return;
        }

        $lastError = error_get_last();

        // Check if a fatal error caused this shutdown
        if (!is_null($lastError) && ErrorTypes::isFatal($lastError['type']) && !$this->client->getConfig()->shouldIgnoreErrorCode($lastError['type'])) {
            $report = Report::fromPHPError(
                $this->client->getConfig(),
                $lastError['type'],
                $lastError['message'],
                $lastError['file'],
                $lastError['line'],
                true
            );

            $report->setSeverity('error');
            $report->setUnhandled(true);
            $report->setSeverityReason([
                'type' => 'unhandledException',
            ]);

            $this->client->notify($report);
        }

        // Flush any buffered errors
        $this->client->flush();
    }
}
