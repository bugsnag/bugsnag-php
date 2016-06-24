<?php

namespace Bugsnag\Pipeline;

interface PipelineInterface
{
    /**
     * Append the given pipe to the pipeline.
     *
     * @param callable $pipe a new pipe to pass through
     *
     * @return $this
     */
    public function pipe(callable $pipe);

    /**
     * Run the pipeline.
     *
     * @param mixed    $passable    the item to send through the pipeline
     * @param callable $destination the final distination callback
     *
     * @return mixed
     */
    public function execute($passable, callable $destination);
}
