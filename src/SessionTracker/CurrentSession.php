<?php

namespace Bugsnag\SessionTracker;

use Bugsnag\Report;

/**
 * In a web application, a Bugsnag session refers to a single HTTP request. This
 * is not the same as the PHP session, but is named this way to align with the
 * Bugsnag session API.
 */
final class CurrentSession
{
    /**
     * The ID of this session.
     *
     * If the session has not been started, this will be null.
     *
     * @var string|null
     */
    private $id;

    /**
     * The time this session was started.
     *
     * If the session has not been started, this will be null.
     *
     * @var string|null
     */
    private $startedAt;

    /**
     * The count of all handled events that took place in this session.
     *
     * @var int
     */
    private $handledCount = 0;

    /**
     * The count of all unhandled events that took place in this session.
     *
     * @var int
     */
    private $unhandledCount = 0;

    /**
     * Start the session at the given time.
     *
     * @param string $currentTime A datetime in the format '%Y-%m-%dT%H:%M:%S'
     *                            e.g. '2000-01-01T12:00:00'
     *
     * @return void
     */
    public function start($currentTime)
    {
        // Do nothing if we've already been started
        if ($this->isActive()) {
            return;
        }

        $this->id = uniqid('', true);
        $this->startedAt = $currentTime;
        $this->handledCount = 0;
        $this->unhandledCount = 0;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return is_string($this->id);
    }

    /**
     * @param Report $report
     *
     * @return void
     */
    public function handle(Report $report)
    {
        // Do nothing if we aren't currently active
        if (!$this->isActive()) {
            return;
        }

        if ($report->getUnhandled()) {
            $this->unhandledCount++;
        } else {
            $this->handledCount++;
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'startedAt' => $this->startedAt,
            'events' => [
                'handled' => $this->handledCount,
                'unhandled' => $this->unhandledCount,
            ],
        ];
    }
}
