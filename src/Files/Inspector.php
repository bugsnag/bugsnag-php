<?php

namespace Bugsnag\Files;

use SplFileObject;

class Inspector
{
    /**
     * The parser instance.
     *
     * @var \Bugsnag\Files\Parser
     */
    protected $parser;

    /**
     * The file to inspect.
     *
     * @var \SplFileObject
     */
    protected $file;

    /**
     * The number of lines in the file.
     *
     * @var int
     */
    protected $lines;

    /**
     * Create a new file inspector instance.
     *
     * @param \Bugsnag\Parser $parser
     * @param \SplFileObject  $file
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    public function __construct(Parser $parser, SplFileObject $file)
    {
        $this->parser = $parser;
        $this->file = $file;
        $file->seek(PHP_INT_MAX);
        $this->lines = $file->key() + 1;
    }

    /**
     * Extract the code for the given file and lines.
     *
     * @param int      $line      the line to centre about
     * @param string   $numLines  the number of lines to fetch
     * @param int|null $maxLength the maximum line length
     *
     * @return string[]
     */
    public function getCode($line, $numLines, $maxLength = null)
    {
        $code = [];

        $bounds = $this->getBounds($line, $numLines);

        $this->file->seek($bounds[0] - 1);

        while ($this->file->key() < $bounds[1]) {
            $code[$this->file->key() + 1] = rtrim($maxLength ? substr($this->file->current(), 0, $maxLength) : $this->file->current());
            $this->file->next();
        }

        return $code;
    }

    /**
     * Extract the tokens for the given file and lines.
     *
     * @param int    $line     the line to centre about
     * @param string $numLines the number of lines to fetch
     *
     * @return array[]
     */
    public function getTokens($line, $numLines)
    {
        $this->file->rewind();

        $contents = $this->file->fread($this->file->getSize());

        $bounds = $this->getBounds($line, $numLines);

        $tokens = (new Parser())->parse($contents, $bounds[0], $bounds[1]);

        return iterator_to_array($tokens);
    }

    /**
     * Get the start and end positions for the given line.
     *
     * @param int    $line     the line to centre about
     * @param string $numLines the number of lines to fetch
     *
     * @return int[]
     */
    protected function getBounds($line, $numLines)
    {
        $start = max($line - floor($numLines / 2), 1);

        $end = $start + ($numLines - 1);

        if ($end > $this->lines) {
            $end = $this->lines;
            $start = max($end - ($numLines - 1), 1);
        }

        return [$start, $end];
    }
}
