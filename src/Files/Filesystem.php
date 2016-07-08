<?php

namespace Bugsnag\Files;

use SplFileObject;

class Filesystem
{
    /**
     * The parser instance.
     *
     * @var \Bugsnag\Files\Parser
     */
    protected $parser;

    /**
     * Create a new filesystem instance.
     *
     * @param \Bugsnag\Parser|null $parser
     *
     * @return void
     */
    public function __construct(Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser();
    }

    /**
     * Get a file inspector for the file at the given path.
     *
     * @param string $path the path to the file
     *
     * @throws \RuntimeException
     *
     * @return \Bugsnag\Files\Inspector
     */
    public function inspect($path)
    {
        $file = new SplFileObject($path);

        return new Inspector($this->parser, $file);
    }
}
