<?php

namespace Bugsnag;

use Exception;
use InvalidArgumentException;
use Throwable;

class Error
{
    /**
     * The payload version.
     *
     * @var int
     */
    const PAYLOAD_VERSION = 2;

    /**
     * The error name.
     *
     * @var string
     */
    public $name;

    /**
     * The error message.
     *
     * @var string|null
     */
    public $message;

    /**
     * The error severity.
     *
     * @var string
     */
    public $severity = 'warning';

    /**
     * The associated stacktrace.
     *
     * @var \Bugsnag\Stacktrace 
     */
    public $stacktrace;

    /**
     * The associated meta data.
     *
     * @var array[]
     */
    public $metaData = [];

    /**
     * The associated user.
     *
     * @var array
     */
    public $user = [];

    /**
     * The associated context.
     *
     * @var string|null
     */
    public $context;

    /**
     * The config object.
     *
     * @var \Bugsnag\Config
     */
    public $config;

    /**
     * The previous error.
     *
     * @var \Bugsnag\Error|null
     */
    public $previous;

    /**
     * The grouping hash.
     *
     * @var string
     */
    public $groupingHash;

    /**
     * Create a new error from a PHP error.
     *
     * @param \Bugsnag\Configuration $config  the config instance
     * @param int                    $code    the error code
     * @param string|null            $message the error message
     * @param string                 $file    the error file
     * @param int                    $line    the error line
     * @param bool                   $fatal   if the error was fatal
     *
     * @return static
     */
    public static function fromPHPError(Configuration $config, $code, $message, $file, $line, $fatal = false)
    {
        $error = new static($config);
        $error->setPHPError($code, $message, $file, $line, $fatal);

        return $error;
    }

    /**
     * Create a new error from a PHP throwable.
     *
     * @param \Bugsnag\Configuration $config    the config instance
     * @param \Throwable             $throwable the throwable instance
     *
     * @return static
     */
    public static function fromPHPThrowable(Configuration $config, $throwable)
    {
        $error = new static($config);
        $error->setPHPThrowable($throwable);

        return $error;
    }

    /**
     * Create a new error from a named error.
     *
     * @param \Bugsnag\Configuration $config  the config instance
     * @param string                 $name    the error name
     * @param string|null            $message the error message
     *
     * @return static
     */
    public static function fromNamedError(Configuration $config, $name, $message = null)
    {
        $error = new static($config);
        $error->setName($name)
              ->setMessage($message)
              ->setStacktrace(Stacktrace::generate($config));

        return $error;
    }

    /**
     * Create a new error instance.
     *
     * This is only for for use only by the static methods above.
     *
     * @param \Bugsnag\Configuration $config the config instance
     *
     * @return void
     */
    protected function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Set the error name.
     *
     * @param string $name the error name
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setName($name)
    {
        if (is_scalar($name) || method_exists($name, '__toString')) {
            $this->name = (string) $name;
        } else {
            throw new InvalidArgumentException('Name must be a string.');
        }

        return $this;
    }

    /**
     * Set the error message.
     *
     * @param string|null $message the error message
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setMessage($message)
    {
        if ($message === null) {
            $this->message = null;
        } elseif (is_scalar($message) || method_exists($message, '__toString')) {
            $this->message = (string) $message;
        } else {
            throw new InvalidArgumentException('Message must be a string.');
        }

        return $this;
    }

    /**
     * Set the grouping hash.
     *
     * @param string $groupingHash the grouping hash
     *
     * @return $this
     */
    public function setGroupingHash($groupingHash)
    {
        $this->groupingHash = $groupingHash;

        return $this;
    }

    /**
     * Set the bugsnag stacktrace.
     *
     * @param \Bugsnag\Stacktrace $stacktrace the stacktrace instance
     *
     * @return $this
     */
    public function setStacktrace(Stacktrace $stacktrace)
    {
        $this->stacktrace = $stacktrace;

        return $this;
    }

    /**
     * Set the error severity.
     *
     * @param string|null $severity the error severity
     *
     * @return $this
     */
    public function setSeverity($severity)
    {
        if (!is_null($severity)) {
            if (in_array($severity, ['error', 'warning', 'info'], true)) {
                $this->severity = $severity;
            } else {
                error_log('Bugsnag Warning: Tried to set error severity to '.$severity.' which is not allowed.');
            }
        }

        return $this;
    }

    /**
     * Set the PHP throwable.
     *
     * @param Throwable $exception the throwable instance
     *
     * @return $this
     */
    public function setPHPThrowable($exception)
    {
        if (interface_exists(Throwable::class)) {
            if (!$exception instanceof Throwable) {
                error_log('Bugsnag Warning: The exception must implement Throwable.');

                return $this;
            }
        } else {
            if (!$exception instanceof Exception) {
                error_log('Bugsnag Warning: The exception must be an Exception.');

                return $this;
            }
        }

        $this->setName(get_class($exception))
             ->setMessage($exception->getMessage())
             ->setStacktrace(Stacktrace::fromBacktrace($this->config, $exception->getTrace(), $exception->getFile(), $exception->getLine()));

        if (method_exists($exception, 'getPrevious')) {
            $this->setPrevious($exception->getPrevious());
        }

        return $this;
    }

    /**
     * Set the PHP error.
     *
     * @param int         $code    the error code
     * @param string|null $message the error message
     * @param string      $file    the error file
     * @param int         $line    the error line
     * @param bool        $fatal   if the error was fatal
     *
     * @return $this
     */
    public function setPHPError($code, $message, $file, $line, $fatal = false)
    {
        if ($fatal) {
            // Generating stacktrace for PHP fatal errors is not possible,
            // since this code executes when the PHP process shuts down,
            // rather than at the time of the crash.
            //
            // In these situations, we generate a "stacktrace" containing only
            // the line and file number where the crash occurred.
            $stacktrace = Stacktrace::fromFrame($this->config, $file, $line);
        } else {
            $stacktrace = Stacktrace::generate($this->config);
        }

        $this->setName(ErrorTypes::getName($code))
             ->setMessage($message)
             ->setSeverity(ErrorTypes::getSeverity($code))
             ->setStacktrace($stacktrace);

        return $this;
    }

    /**
     * Set the error meta data.
     *
     * @param array[] $metaData an array of arrays of custom data
     * @param bool    $merge    should we merge the meta data
     *
     * @return $this
     */
    public function setMetaData(array $metaData, $merge = true)
    {
        $this->metaData = $merge ? array_merge_recursive($this->metaData, $metaData) : $metaData;

        return $this;
    }

    /**
     * Set the current user.
     *
     * @param array $user the current user
     *
     * @return $this
     */
    public function setUser(array $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set a context representing the current type of request, or location in code.
     *
     * @param string|null $context the current context
     *
     * @return $this
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Set the previous throwable.
     *
     * @param \Throwable $exception the previous throwable
     *
     * @return $this
     */
    public function setPrevious($exception)
    {
        if ($exception) {
            $this->previous = static::fromPHPThrowable($this->config, $exception);
        }

        return $this;
    }

    /**
     * Get the array representation.
     *
     * @return array
     */
    public function toArray()
    {
        $errorArray = [
            'app' => $this->config->getAppData(),
            'device' => $this->config->getDeviceData(),
            'user' => $this->user,
            'context' => $this->context,
            'payloadVersion' => static::PAYLOAD_VERSION,
            'severity' => $this->severity,
            'exceptions' => $this->exceptionArray(),
            'metaData' => $this->cleanupObj($this->metaData, true),
        ];

        if ($this->groupingHash) {
            $errorArray['groupingHash'] = $this->groupingHash;
        }

        return $errorArray;
    }

    /**
     * Get the exception array.
     *
     * @return array
     */
    protected function exceptionArray()
    {
        $exceptionArray = $this->previous ? $this->previous->exceptionArray() : [];

        $exceptionArray[] = [
            'errorClass' => $this->name,
            'message' => $this->message,
            'stacktrace' => $this->stacktrace->toArray(),
        ];

        return $this->cleanupObj($exceptionArray, false);
    }

    /**
     * Cleanup the given object.
     *
     * @param mixed $obj        the data to cleanup
     * @param bool  $isMetaData if it is meta data
     *
     * @return array|null
     */
    protected function cleanupObj($obj, $isMetaData)
    {
        if (is_null($obj)) {
            return;
        }

        if (is_array($obj)) {
            $clean = [];

            foreach ($obj as $key => $value) {
                $clean[$key] = $this->shouldFilter($key, $isMetaData) ? '[FILTERED]' : $this->cleanupObj($value, $isMetaData);
            }

            return $clean;
        }

        if (is_string($obj)) {
            return (function_exists('mb_detect_encoding') && !mb_detect_encoding($obj, 'UTF-8', true)) ? utf8_encode($obj) : $obj;
        }

        if (is_object($obj)) {
            return $this->cleanupObj(json_decode(json_encode($obj), true), $isMetaData);
        }

        return $obj;
    }

    /**
     * Should we filter the given element.
     *
     * @param string $key        the associated key
     * @param bool   $isMetaData if it is meta data
     *
     * @return bool
     */
    protected function shouldFilter($key, $isMetaData)
    {
        if ($isMetaData) {
            foreach ($this->config->getFilters() as $filter) {
                if (strpos($key, $filter) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}
