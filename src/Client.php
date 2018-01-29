<?php

namespace Bugsnag;

use BadMethodCallException;
use Bugsnag\Breadcrumbs\Breadcrumb;
use Bugsnag\Breadcrumbs\Recorder;
use Bugsnag\Callbacks\GlobalMetaData;
use Bugsnag\Callbacks\RequestContext;
use Bugsnag\Callbacks\RequestCookies;
use Bugsnag\Callbacks\RequestMetaData;
use Bugsnag\Callbacks\RequestSession;
use Bugsnag\Callbacks\RequestUser;
use Bugsnag\Middleware\BreadcrumbData;
use Bugsnag\Middleware\CallbackBridge;
use Bugsnag\Middleware\NotificationSkipper;
use Bugsnag\Middleware\SessionData;
use Bugsnag\Request\BasicResolver;
use Bugsnag\Request\ResolverInterface;
use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\ClientInterface;
use ReflectionClass;
use ReflectionException;

class Client
{
    /**
     * The default endpoint.
     *
     * @var string
     */
    const ENDPOINT = 'https://notify.bugsnag.com';

    /**
     * The config instance.
     *
     * @var \Bugsnag\Configuration
     */
    protected $config;

    /**
     * The request resolver instance.
     *
     * @var \Bugsnag\Request\ResolverInterface
     */
    protected $resolver;

    /**
     * The breadcrumb recorder instance.
     *
     * @var \Bugsnag\Breadcrumbs\Recorder
     */
    protected $recorder;

    /**
     * The notification pipeline instance.
     *
     * @var \Bugsnag\Pipeline
     */
    protected $pipeline;

    /**
     * The http client instance.
     *
     * @var \Bugsnag\HttpClient
     */
    protected $http;

    /**
     * The session tracker instance.
     *
     * @var \Bugsnag\SessionTracker
     */
    protected $sessionTracker;

    /**
     * Make a new client instance.
     *
     * If you don't pass in a key, we'll try to read it from the env variables.
     *
     * @param string|null $apiKey   your bugsnag api key
     * @param string|null $endpoint your bugsnag endpoint
     * @param bool        $default  if we should register our default callbacks
     *
     * @return static
     */
    public static function make($apiKey = null, $endpoint = null, $defaults = true)
    {
        $config = new Configuration($apiKey ?: getenv('BUGSNAG_API_KEY'));
        $guzzle = static::makeGuzzle($endpoint ?: getenv('BUGSNAG_ENDPOINT'));

        $client = new static($config, null, $guzzle);

        if ($defaults) {
            $client->registerDefaultCallbacks();
        }

        return $client;
    }

    /**
     * Create a new client instance.
     *
     * @param \Bugsnag\Configuration                  $config
     * @param \Bugsnag\Request\ResolverInterface|null $resolver
     * @param \GuzzleHttp\ClientInterface|null        $guzzle
     *
     * @return void
     */
    public function __construct(Configuration $config, ResolverInterface $resolver = null, ClientInterface $guzzle = null)
    {
        $this->config = $config;
        $this->resolver = $resolver ?: new BasicResolver();
        $this->recorder = new Recorder();
        $this->pipeline = new Pipeline();
        $this->http = new HttpClient($config, $guzzle ?: static::makeGuzzle());
        $this->sessionTracker = new SessionTracker($config);

        $this->pipeline->pipe(new NotificationSkipper($config));
        $this->pipeline->pipe(new BreadcrumbData($this->recorder));
        $this->pipeline->pipe(new SessionData($this));

        register_shutdown_function([$this, 'flush']);
    }

    /**
     * Make a new guzzle client instance.
     *
     * @param string|null $base
     * @param array       $options
     *
     * @return \GuzzleHttp\ClientInterface
     */
    public static function makeGuzzle($base = null, array $options = [])
    {
        $key = version_compare(ClientInterface::VERSION, '6') === 1 ? 'base_uri' : 'base_url';

        $options[$key] = $base ?: static::ENDPOINT;

        if ($path = static::getCaBundlePath()) {
            $options['verify'] = $path;
        }

        return new Guzzle($options);
    }

    /**
     * Get the ca bundle path if one exists.
     *
     * @return string|false
     */
    protected static function getCaBundlePath()
    {
        if (!class_exists(CaBundle::class)) {
            return false;
        }

        return realpath(CaBundle::getSystemCaRootBundlePath());
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
     * Regsier a new notification callback.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function registerCallback(callable $callback)
    {
        $this->pipeline->pipe(new CallbackBridge($callback));

        return $this;
    }

    /**
     * Regsier all our default callbacks.
     *
     * @return $this
     */
    public function registerDefaultCallbacks()
    {
        $this->registerCallback(new GlobalMetaData($this->config))
             ->registerCallback(new RequestMetaData($this->resolver))
             ->registerCallback(new RequestCookies($this->resolver))
             ->registerCallback(new RequestSession($this->resolver))
             ->registerCallback(new RequestUser($this->resolver))
             ->registerCallback(new RequestContext($this->resolver));

        return $this;
    }

    /**
     * Record the given breadcrumb.
     *
     * @param string      $name     the name of the breadcrumb
     * @param string|null $type     the type of breadcrumb
     * @param array       $metaData additional information about the breadcrumb
     *
     * @return void
     */
    public function leaveBreadcrumb($name, $type = null, array $metaData = [])
    {
        try {
            $name = (new ReflectionClass($name))->getShortName();
        } catch (ReflectionException $e) {
            //
        }

        $name = substr((string) $name, 0, Breadcrumb::MAX_LENGTH);

        $type = in_array($type, Breadcrumb::getTypes(), true) ? $type : Breadcrumb::MANUAL_TYPE;

        $this->recorder->record(new Breadcrumb($name, $type, $metaData));
    }

    /**
     * Clear all recorded breadcrumbs.
     *
     * @return void
     */
    public function clearBreadcrumbs()
    {
        $this->recorder->clear();
    }

    /**
     * Notify Bugsnag of a non-fatal/handled throwable.
     *
     * @param \Throwable    $throwable the throwable to notify Bugsnag about
     * @param callable|null $callback  the customization callback
     *
     * @return void
     */
    public function notifyException($throwable, callable $callback = null)
    {
        $report = Report::fromPHPThrowable($this->config, $throwable);

        $this->notify($report, $callback);
    }

    /**
     * Notify Bugsnag of a non-fatal/handled error.
     *
     * @param string        $name     the name of the error, a short (1 word) string
     * @param string        $message  the error message
     * @param callable|null $callback the customization callback
     *
     * @return void
     */
    public function notifyError($name, $message, callable $callback = null)
    {
        $report = Report::fromNamedError($this->config, $name, $message);

        $this->notify($report, $callback);
    }

    /**
     * Notify Bugsnag of the given error report.
     *
     * This may simply involve queuing it for later if we're batching.
     *
     * @param \Bugsnag\Report $report   the error report to send
     * @param callable|null   $callback the customization callback
     *
     * @return void
     */
    public function notify(Report $report, callable $callback = null)
    {
        $initialUnhandled = $report->getUnhandled();
        $initialSeverity = $report->getSeverity();
        $initialReason = $report->getSeverityReason();
        $this->pipeline->execute($report, function ($report) use ($callback, $initialUnhandled, $initialSeverity, $initialReason) {
            if ($callback) {
                if ($callback($report) === false) {
                    return;
                }
            }

            $report->setUnhandled($initialUnhandled);
            if ($report->getSeverity() != $initialSeverity) {
                // Severity has been changed via callbacks -> severity reason should be userCallbackSetSeverity
                $report->setSeverityReason([
                    'type' => 'userCallbackSetSeverity',
                ]);
            } else {
                // Otherwise we ensure the original severity reason is preserved
                $report->setSeverityReason($initialReason);
            }

            $this->http->queue($report);
        });

        $this->leaveBreadcrumb($report->getName() ?: 'Error', Breadcrumb::ERROR_TYPE, $report->getSummary());

        if (!$this->config->isBatchSending()) {
            $this->flush();
        }
    }

    /**
     * Notify Bugsnag of a deployment.
     *
     * @deprecated This function is being deprecated in favour of `build`.
     *
     * @param string|null $repository the repository from which you are deploying the code
     * @param string|null $branch     the source control branch from which you are deploying
     * @param string|null $revision   the source control revision you are currently deploying
     *
     * @return void
     */
    public function deploy($repository = null, $branch = null, $revision = null)
    {
        $this->build($repository, $revision);
    }

    /**
     * Notify Bugsnag of a build.
     *
     * @param string|null $repository  the repository from which you are deploying the code
     * @param string|null $revision    the source control revision you are currently deploying
     * @param string|null $provider    the provider of the source control for the build
     * @param string|null $builderName the name of who or what is making the build
     *
     * @return void
     */
    public function build($repository = null, $revision = null, $provider = null, $builderName = null)
    {
        $data = [];

        if ($repository) {
            $data['repository'] = $repository;
        }

        if ($revision) {
            $data['revision'] = $revision;
        }

        if ($provider) {
            $data['provider'] = $provider;
        }

        if ($builderName) {
            $data['builder'] = $builderName;
        }

        $this->http->sendBuildReport($data);
    }

    /**
     * Flush any buffered reports.
     *
     * @return void
     */
    public function flush()
    {
        $this->http->send();
    }

    /**
     * Start tracking a session.
     *
     * @return void
     */
    public function startSession()
    {
        $this->sessionTracker->startSession();
    }

    /**
     * Returns the session tracker.
     *
     * @return \Bugsnag\SessionTracker
     */
    public function getSessionTracker()
    {
        return $this->sessionTracker;
    }

    /**
     * Dynamically pass calls to the configuration.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $callable = [$this->config, $method];

        if (!is_callable($callable)) {
            throw new BadMethodCallException("The method '{$method}' does not exist.");
        }

        $value = call_user_func_array($callable, $parameters);

        return stripos($method, 'set') === 0 ? $this : $value;
    }
}
