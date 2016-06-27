<?php

namespace Bugsnag;

use Bugsnag\Request\BasicResolver;
use Bugsnag\Middleware\AddGlobalMetaData;
use Bugsnag\Middleware\AddRequestCookieData;
use Bugsnag\Middleware\AddRequestMetaData;
use Bugsnag\Middleware\AddRequestSessionData;
use Bugsnag\Middleware\NotificationSkipper;
use Bugsnag\Request\ResolverInterface;
use Exception;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\ClientInterface;
use Throwable;

class Client
{
    const ENDPOINT = 'https://notify.bugsnag.com';

    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    protected $config;

    /**
     * The notification pipeline instance.
     *
     * @var \Bugsnag\Pipeline
     */
    protected $pipeline;

    /**
     * The request resolver instance.
     *
     * @var \Bugsnag\Request\ResolverInterface
     */
    protected $resolver;

    /**
     * The diagnostics instance.
     *
     * @var \Bugsnag\Diagnostics
     */
    protected $diagnostics;

    /**
     * The guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $guzzle;

    /**
     * The notification instance.
     *
     * @var \Bugsnag\Notification|null
     */
    protected $notification;

    /**
     * Make a new client instance.
     *
     * If you don't pass in a key, we'll try to read it from the env variables.
     *
     * @param string|null $apiKey   your bugsnag api key
     * @param string|null $endpoint your bugsnag endpoint
     * @param bool        $default  if we should register our default middleware
     *
     * @return static
     */
    public static function make($apiKey = null, $endpoint = null, $defaults = true)
    {
        $config = new Configuration($apiKey ?: getenv('BUGSNAG_API_KEY'));
        $guzzle = new Guzzle(['base_uri' => ($endpoint ?: getenv('BUGSNAG_ENDPOINT')) ?: static::ENDPOINT]);

        $client = new static($config, null, null, $guzzle);

        if ($defaults) {
            $client->registerDefaultMiddleware();
        }

        return $client;
    }

    /**
     * Create a new client instance.
     *
     * @param \Bugsnag\Configuration                  $config
     * @param \Bugsnag\Pipeline|null                  $pipeline
     * @param \Bugsnag\Request\ResolverInterface|null $resolver
     * @param \GuzzleHttp\ClientInterface|null        $guzzle
     *
     * @return void
     */
    public function __construct(Configuration $config, Pipeline $pipeline = null, ResolverInterface $resolver = null, ClientInterface $guzzle = null)
    {
        $this->config = $config;
        $this->pipeline = $pipeline ?: new Pipeline();
        $this->resolver = $resolver ?: new BasicResolver();
        $this->diagnostics = new Diagnostics($this->config, $this->resolver);
        $this->guzzle = $guzzle ?: new Guzzle(['base_uri' => static::ENDPOINT]);

        register_shutdown_function([$this, 'shutdownHandler']);
    }

    /**
     * Get the config instance.
     *
     * @return \Bugsnag\Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the notification pipeline instance.
     *
     * @return \Bugsnag\Pipeline
     */
    public function getPipeline()
    {
        return $this->pipeline;
    }

    /**
     * Get the request resolver instance.
     *
     * @return \Bugsnag\Request\ResolverInterface
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * Get the diagnostics instance.
     *
     * @return \Bugsnag\Diagnostics
     */
    public function getDiagnostics()
    {
        return $this->diagnostics;
    }

    /**
     * Get the config instance.
     *
     * @return \GuzzleHttp\ClientInterface
     */
    public function getGuzzle()
    {
        return $this->guzzle;
    }

    /**
     * Regsier all our default middleware.
     *
     * @return $this
     */
    public function registerDefaultMiddleware()
    {
        $this->pipeline->pipe(new AddGlobalMetaData())
                       ->pipe(new AddRequestMetaData())
                       ->pipe(new AddRequestCookieData())
                       ->pipe(new AddRequestSessionData())
                       ->pipe(new NotificationSkipper());

        return $this;
    }

    /**
     * Set your release stage, eg "production" or "development".
     *
     * @param string $releaseStage the app's current release stage
     *
     * @return $this
     */
    public function setReleaseStage($releaseStage)
    {
        $this->config->releaseStage = $releaseStage;

        return $this;
    }

    /**
     * Set your app's semantic version, eg "1.2.3".
     *
     * @param string $appVersion the app's version
     *
     * @return $this
     */
    public function setAppVersion($appVersion)
    {
        $this->config->appVersion = $appVersion;

        return $this;
    }

    /**
     * Set the host name.
     *
     * @param string $hostname the host name
     *
     * @return $this
     */
    public function setHostname($hostname)
    {
        $this->config->hostname = $hostname;

        return $this;
    }

    /**
     * Set which release stages should be allowed to notify Bugsnag.
     *
     * Eg ['production', 'development'].
     *
     * @param array $notifyReleaseStages array of release stages to notify for
     *
     * @return $this
     */
    public function setNotifyReleaseStages(array $notifyReleaseStages)
    {
        $this->config->notifyReleaseStages = $notifyReleaseStages;

        return $this;
    }

    /**
     * Set the absolute path to the root of your application.
     *
     * We use this to help with error grouping and to highlight "in project"
     * stacktrace lines.
     *
     * @param string $projectRoot the root path for your application
     *
     * @return $this
     */
    public function setProjectRoot($projectRoot)
    {
        $this->config->setProjectRoot($projectRoot);

        return $this;
    }

    /**
     * Set the absolute split path.
     *
     * This is the path that should be stripped from the beginning of any
     * stacktrace file line. This helps to normalise filenames for grouping
     * and reduces the noise in stack traces.
     *
     * @param string $stripPath the path to strip from filenames
     *
     * @return $this
     */
    public function setStripPath($stripPath)
    {
        $this->config->setStripPath($stripPath);

        return $this;
    }

    /**
     * Set the a regular expression for matching filenames in stacktrace lines.
     *
     * @param string $projectRootRegex regex matching paths belong to your project
     *
     * @return $this
     */
    public function setProjectRootRegex($projectRootRegex)
    {
        $this->config->projectRootRegex = $projectRootRegex;

        return $this;
    }

    /**
     * Set the strings to filter out from metaData arrays before sending then.
     *
     * Eg. ['password', 'credit_card'].
     *
     * @param array $filters an array of metaData filters
     *
     * @return $this
     */
    public function setFilters(array $filters)
    {
        $this->config->filters = $filters;

        return $this;
    }

    /**
     * Set information about the current user of your app, including id, name and email.
     *
     * @param array $user an array of user information. Eg:
     *        [
     *            'name' => 'Bob Hoskins',
     *            'email' => 'bob@hoskins.com'
     *        ]
     *
     * @return $this
     */
    public function setUser(array $user)
    {
        $this->config->user = $user;

        return $this;
    }

    /**
     * Set a context representing the current type of request, or location in code.
     *
     * @param string $context the current context
     *
     * @return $this
     */
    public function setContext($context)
    {
        $this->config->context = $context;

        return $this;
    }

    /**
     * Set the type of application executing the code.
     *
     * This is usually used to represent if you are running plain PHP code
     * "php", via a framework, eg "laravel", or executing through delayed
     * worker code, eg "resque".
     *
     * @param string $type the current type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->config->type = $type;

        return $this;
    }

    /**
     * Set custom metadata to send to Bugsnag with every error.
     *
     * You can use this to add custom tabs of data to each error on your
     * Bugsnag dashboard.
     *
     * @param array $metaData an array of arrays of custom data. Eg:
     *        [
     *            'user' => [
     *                'name' => 'James',
     *                'email' => 'james@example.com'
     *            ]
     *        ]
     * @param bool $merge optionally merge the meta data
     *
     * @return $this
     */
    public function setMetaData(array $metaData, $merge = false)
    {
        if ($merge) {
            $this->config->metaData = array_merge_recursive((array) $this->config->metaData, $metaData);
        } else {
            $this->config->metaData = $metaData;
        }

        return $this;
    }

    /**
     * Set Bugsnag's error reporting level.
     *
     * If this is not set, we'll use your current PHP error_reporting value
     * from your ini file or error_reporting(...) calls.
     *
     * @param int $errorReportingLevel the error reporting level integer
     *                exactly as you would pass to PHP's error_reporting
     *
     * @return $this
     */
    public function setErrorReportingLevel($errorReportingLevel)
    {
        $this->config->errorReportingLevel = $errorReportingLevel;

        return $this;
    }

    /**
     * Sets whether errors should be batched together and send at the end of each request.
     *
     * @param bool $batchSending whether to batch together errors
     *
     * @return $this
     */
    public function setBatchSending($batchSending)
    {
        $this->config->batchSending = $batchSending;

        return $this;
    }

    /**
     * Sets the notifier to report as to Bugsnag.
     *
     * This should only be set by other notifier libraries.
     *
     * @param array $notifier an array of name, version, url.
     *
     * @return $this
     */
    public function setNotifier($notifier)
    {
        $this->config->notifier = $notifier;

        return $this;
    }

    /**
     * Should we send a small snippet of the code that crashed.
     *
     * This can help you diagnose even faster from within your dashboard.
     *
     * @param bool $sendCode whether to send code to Bugsnag
     *
     * @return $this
     */
    public function setSendCode($sendCode)
    {
        $this->config->sendCode = $sendCode;

        return $this;
    }

    /**
     * Notify Bugsnag of a non-fatal/handled throwable.
     *
     * @param \Throwable $throwable the throwable to notify Bugsnag about
     * @param array      $metaData  optional metaData to send with this error
     * @param string     $severity  optional severity of this error (fatal/error/warning/info)
     *
     * @return void
     */
    public function notifyException($throwable, array $metaData = null, $severity = null)
    {
        if ($throwable instanceof Throwable || $throwable instanceof Exception) {
            $error = Error::fromPHPThrowable($this->config, $this->diagnostics, $throwable);
            $error->setSeverity($severity);

            $this->notify($error, $metaData);
        }
    }

    /**
     * Notify Bugsnag of a non-fatal/handled error.
     *
     * @param string $name     the name of the error, a short (1 word) string
     * @param string $message  the error message
     * @param array  $metaData optional metaData to send with this error
     * @param string $severity optional severity of this error (fatal/error/warning/info)
     *
     * @return void
     */
    public function notifyError($name, $message, array $metaData = null, $severity = null)
    {
        $error = Error::fromNamedError($this->config, $this->diagnostics, $name, $message);
        $error->setSeverity($severity);

        $this->notify($error, $metaData);
    }

    /**
     * Batches up errors into notifications for later sending.
     *
     * @param \Bugsnag\Error $error    the error to batch up
     * @param array          $metaData optional meta data to send with the error
     *
     * @return void
     */
    public function notify(Error $error, $metaData = [])
    {
        // Queue or send the error
        if ($this->sendErrorsOnShutdown()) {
            // Create a batch notification unless we already have one
            if (is_null($this->notification)) {
                $this->notification = new Notification($this->config, $this->pipeline, $this->guzzle);
            }

            // Add this error to the notification
            $this->notification->addError($error, $metaData);
        } else {
            // Create and deliver notification immediately
            $notif = new Notification($this->config, $this->pipeline, $this->guzzle);
            $notif->addError($error, $metaData);
            $notif->deliver();
        }
    }

    /**
     * Should we send errors immediately, or on shutdown?
     *
     * @return bool
     */
    protected function sendErrorsOnShutdown()
    {
        return $this->config->batchSending && $this->resolver->resolve()->isRequest();
    }

    /**
     * Flush any buffered errors.
     *
     * @return void
     */
    public function shutdownHandler()
    {
        if ($this->notification) {
            $this->notification->deliver();
            $this->notification = null;
        }
    }
}
