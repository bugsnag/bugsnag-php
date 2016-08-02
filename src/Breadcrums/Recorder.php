<?php

namespace Bugsnag\Breadcrums;

use Iterator;

class Recorder implements Iterator
{
    /**
     * The maximum number of breadcrums to store.
     *
     * @var int
     */
    const MAX_ITEMS = 25;

    /**
     * The recorded breadcrums.
     *
     * @var \Bugsnag\Breadcrums\Breadcrum[]
     */
    protected $breadcrums = [];

    /**
     * The head position.
     *
     * @var int
     */
    protected $head = 0;

    /**
     * The pointer position.
     *
     * @var int
     */
    protected $pointer = 0;

    /**
     * The iteration position.
     *
     * @var int
     */
    protected $position = 0;

    /**
     * Record a breadcrum.
     *
     * We're recording a maximum of 25 breadcrums. Once we've recorded 25, we
     * start wrapping back around and replacing the earlier ones. In order to
     * indicate the start of the list, we advance a head pointer.
     *
     * @param \Bugsnag\Breadcrums\Breadcrum $breadcrum
     *
     * @return void
     */
    public function record(Breadcrum $breadcrum)
    {
        // advance the head by one if we've caught up
        if ($this->breadcrums && $this->pointer === $this->head) {
            $this->head = ($this->head + 1) % static::MAX_ITEMS;
        }

        // record the new breadcrum
        $this->breadcrums[$this->pointer] = $breadcrum;

        // advance the pointer so we set the next breadcrum in the next slot
        $this->pointer = ($this->pointer + 1) % static::MAX_ITEMS;
    }

    /**
     * Get the current item.
     *
     * @return \Bugsnag\Breadcrums\Breadcrum
     */
    public function current()
    {
        return $this->breadcrums[$this->key()];
    }
    
    /**
     * Get the current key.
     *
     * @return int
     */
    public function key()
    {
        // note that the position will move from 0 -> 24
        return ($this->head + $this->position) % static::MAX_ITEMS;
    }
    
    /**
     * Advance the key position.
     *
     * @return void
     */
    public function next()
    {
        $this->position++;
    }
    
    /**
     * Rewind the key position.
     *
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }
    
    /**
     * Is the current key position set?
     *
     * @return int
     */
    public function valid()
    {
        return $this->position < count($breadcrums);
    }
}
