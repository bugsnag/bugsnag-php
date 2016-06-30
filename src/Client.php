<?php

namespace Bugsnag;

use Bugsnag\Middleware\AddGlobalMetaData;
use Bugsnag\Middleware\AddRequestContext;
use Bugsnag\Middleware\AddRequestCookieData;
use Bugsnag\Middleware\AddRequestMetaData;
use Bugsnag\Middleware\AddRequestSessionData;
use Bugsnag\Middleware\AddRequestUser;
use Bugsnag\Middleware\NotificationSkipper;
use Bugsnag\Request\BasicResolver;
use Bugsnag\Request\ResolverInterface;
use Exception;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\ClientInterface;
use Throwable;

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

        $client = new static($config, null, $guzzle);

        if ($defaults) {
            $client->registerDefaultMiddleware();
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
        $this->pipeline = new Pipeline();
        $this->http = new HttpClient($config, $guzzle ?: new Guzzle(['base_uri' => static::ENDPOINT]));

        $this->registerMiddleware(new NotificationSkipper($config));

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
     * Regsier all our default middleware.
     *
     * @return $this
     */
    public function registerDefaultMiddleware()
    {
        $this->pipeline->pipe(new AddGlobalMetaData($this->config))
                       ->pipe(new AddRequestMetaData($this->resolver))
                       ->pipe(new AddRequestCookieData($this->resolver))
                       ->pipe(new AddRequestSessionData($this->resolver))
                       ->pipe(new AddRequestUser($this->resolver))
                       ->pipe(new AddRequestContext($this->resolver));

        return $this;
    }

    /**
     * Regsier a new notification middleware.
     *
     * @param callable $middleware
     *
     * @return $this
     */
    public function registerMiddleware(callable $middleware)
    {
        $this->pipeline->pipe($middleware);

        return $this;
    }

    /**
     * Notify Bugsnag of a non-fatal/handled throwable.
     *
     * @param \Throwable  $throwable the throwable to notify Bugsnag about
     * @param array[]     $metaData  optional metaData to send with this error
     * @param string|null $severity  optional severity of this error (fatal/error/warning/info)
     *
     * @return void
     */
    public function notifyException($throwable, array $metaData = [], $severity = null)
    {
        if ($throwable instanceof Throwable || $throwable instanceof Exception) {
            $error = Error::fromPHPThrowable($this->config, $throwable);
            $error->setSeverity($severity);

            $this->notify($error, $metaData);
        }
    }

    /**
     * Notify Bugsnag of a non-fatal/handled error.
     *
     * @param string      $name     the name of the error, a short (1 word) string
     * @param string      $message  the error message
     * @param array[]     $metaData optional metaData to send with this error
     * @param string|null $severity optional severity of this error (fatal/error/warning/info)
     *
     * @return void
     */
    public function notifyError($name, $message, array $metaData = [], $severity = null)
    {
        $error = Error::fromNamedError($this->config, $name, $message);
        $error->setSeverity($severity);

        $this->notify($error, $metaData);
    }

    /**
     * Batches up errors into notifications for later sending.
     *
     * @param \Bugsnag\Error $error    the error to batch up
     * @param array[]        $metaData optional meta data to send with the error
     *
     * @return void
     */
    public function notify(Error $error, $metaData = [])
    {
        $this->pipeline->execute($error, function ($error) use ($metaData) {
            if ($metaData) {
                $error->setMetaData($metaData);
            }

            $this->http->queue($error);
        });

        if (!$this->sendErrorsOnShutdown()) {
            $this->http->send();
        }
    }

    /**
     * Should we send errors immediately, or on shutdown?
     *
     * @return bool
     */
    protected function sendErrorsOnShutdown()
    {
        return $this->config->isBatchSending() && $this->resolver->resolve()->isRequest();
    }

    /**
     * Flush any buffered errors.
     *
     * @return void
     */
    public function shutdownHandler()
    {
        $this->http->send();
    }

    /**
     * Dynamically pass calls to the configuration.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $value = call_user_func_array([$this->config, $method], $parameters);

        return stripos($method, 'set') === 0 ? $this : $value;
    }
}
