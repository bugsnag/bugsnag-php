<?php

namespace Bugsnag\Breadcrumbs;

use InvalidArgumentException;

class Breadcrumb
{
    /**
     * The navigation type.
     *
     * @var string
     */
    const NAVIGATION_TYPE = 'navigation';

    /**
     * The request type.
     *
     * @var string
     */
    const REQUEST_TYPE = 'request';

    /**
     * The process type.
     *
     * @var string
     */
    const PROCESS_TYPE = 'process';

    /**
     * The log type.
     *
     * @var string
     */
    const LOG_TYPE = 'log';

    /**
     * The user type.
     *
     * @var string
     */
    const USER_TYPE = 'user';

    /**
     * The state type.
     *
     * @var string
     */
    const STATE_TYPE = 'state';

    /**
     * The error type.
     *
     * @var string
     */
    const ERROR_TYPE = 'error';

    /**
     * The manual type.
     *
     * @var string
     */
    const MANUAL_TYPE = 'manual';

    /**
     * The maximum length of the name.
     *
     * @var int
     */
    const MAX_LENGTH = 30;

    /**
     * The maximum size of the breadcrumb.
     *
     * @var int
     */
    const MAX_SIZE = 4096;

    /**
     * The timestamp of the breadcrumb.
     *
     * @var string
     */
    protected $timestamp;

    /**
     * The name of the breadcrumb.
     *
     * @var string
     */
    protected $name;

    /**
     * The type of the breadcrumb.
     *
     * @var string
     */
    protected $type;

    /**
     * The meta data of the breadcrumb.
     *
     * @var array
     */
    protected $metaData;

    /**
     * Create a new breadcrumb instance.
     *
     * @param string $name     the name of the breadcrumb
     * @param string $type     the type of breadcrumb
     * @param array  $metaData additional information about the breadcrumb
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function __construct($name, $type, array $metaData = [])
    {
        if (!is_string($name) || $name === '') {
            throw new InvalidArgumentException('The breadcrumb name must be a non-empty string.');
        }

        if (strlen($name) > static::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('The breadcrumb name must be at most %d characters in length.', static::MAX_LENGTH));
        }

        $types = static::getTypes();

        if (!in_array($type, $types, true)) {
            throw new InvalidArgumentException(sprintf('The breadcrumb type must be one of the set of %d standard types.', count($types)));
        }

        $this->timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $this->name = $name;
        $this->type = $type;
        $this->metaData = $metaData;
    }

    /**
     * Get the breadcrumb as an array.
     *
     * Note that this is without the meta data.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'timestamp' => $this->timestamp,
            'name' => $this->name,
            'type' => $this->type,
        ];
    }

    /**
     * Get the breadcrumb meta data.
     *
     * Note that this still needs sanitizing before use.
     *
     * @return array
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * Get the set of valid breadrum types.
     *
     * @return array
     */
    public static function getTypes()
    {
        return [
            static::NAVIGATION_TYPE,
            static::REQUEST_TYPE,
            static::PROCESS_TYPE,
            static::LOG_TYPE,
            static::USER_TYPE,
            static::STATE_TYPE,
            static::ERROR_TYPE,
            static::MANUAL_TYPE,
        ];
    }
}
