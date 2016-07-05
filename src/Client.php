<?php

namespace Bugsnag;

use Bugsnag\Callbacks\CustomUser;
use Bugsnag\Callbacks\GlobalMetaData;
use Bugsnag\Callbacks\RequestContext;
use Bugsnag\Callbacks\RequestCookies;
use Bugsnag\Callbacks\RequestMetaData;
use Bugsnag\Callbacks\RequestSession;
use Bugsnag\Callbacks\RequestUser;
use Bugsnag\Middleware\CallbackBridge;
use Bugsnag\Middleware\NotificationSkipper;
use Bugsnag\Request\BasicResolver;
use Bugsnag\Request\ResolverInterface;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\ClientInterface;

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
     * @param bool        $default  if we should register our default callbacks
     *
     * @return static
     */
    public static function make($apiKey = null, $endpoint = null, $defaults = true)
    {
        $config = new Configuration($apiKey ?: getenv('BUGSNAG_API_KEY'));
        $guzzle = new Guzzle(['base_uri' => ($endpoint ?: getenv('BUGSNAG_ENDPOINT')) ?: static::ENDPOINT]);

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
        $this->pipeline = new Pipeline();
        $this->http = new HttpClient($config, $guzzle ?: new Guzzle(['base_uri' => static::ENDPOINT]));

        $this->registerMiddleware(new NotificationSkipper($config));

        register_shutdown_function([$this, 'flush']);
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
     * Regsier a new notification callback.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function registerCallback(callable $callback)
    {
        $this->registerMiddleware(new CallbackBridge($callback));

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
     * Register the current user resolver.
     *
     * @param callable $resolver the user resolver callback
     *
     * @return $this
     */
    public function registerUserResolver(callable $resolver)
    {
        $this->registerCallback(new CustomUser($resolver));

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
        $error = Error::fromPHPThrowable($this->config, $throwable);

        if ($severity) {
            $error->setSeverity($severity);
        }

        $this->notify($error, $metaData);
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

        if ($severity) {
            $error->setSeverity($severity);
        }

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

        if (!$this->config->isBatchSending()) {
            $this->flush();
        }
    }

    /**
     * Flush any buffered errors.
     *
     * @return void
     */
    public function flush()
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
