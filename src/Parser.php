<?php

namespace Bugsnag;

use Generator;

class Parser
{
    /**
     * The number of tokens to send before.
     *
     * @var int
     */
    const BEFORE = 60;

    /**
     * The number of tokens to send after.
     *
     * @var int
     */
    const AFTER = 40;

    /**
     * The maximum size of the tokens.
     *
     * @var int
     */
    const MAX_SIZE = 65536;

    /**
     * Parse the file at the given path.
     *
     * You may optionally specify the region to restrict the result too.
     *
     * @param string   $content
     * @param int      $line
     * @param int|null $before
     * @param int|null $after
     *
     * @return array
     */
    public function parse($content, $line, $before = null, $after = null)
    {
        $tokens = $this->tokenize($content);

        $transformed = $this->transform($tokens);

        return $this->filter($transformed, $line, $before, $after);
    }

    /**
     * Tokenize the given code using PHP's tokenizer.
     *
     * @param string $content
     *
     * @return \Generator
     */
    protected function tokenize($content)
    {
        foreach (token_get_all($content) as $token) {
            yield $token;
        }
    }

    /**
     * Transform the given set of tokens into a friendly representation.
     *
     * @param \Generator $tokens
     *
     * @return \Generator
     */
    protected function transform(Generator $tokens)
    {
        $previous = 1;

        foreach ($tokens as $token) {
            $new = $this->generate($token, $previous);

            $previous = $new['line'];

            yield $new;
        }
    }

    /**
     * Generate a friendly token representation.
     *
     * @param array|string $token
     * @param int          $previous
     *
     * @return array
     */
    protected function generate($token, $previous)
    {
        if (is_array($token)) {
            return [
                'token' => token_name($token[0]),
                'content' => $token[1],
                'line' => $token[2],
            ];
        }

        return [
            'token' => 'T_OTHER',
            'content' => $token,
            'line' => $previous,
        ];
    }

    /**
     * Filter out the tokens outside the area we're interested in.
     *
     * @param \Generator $transformed
     * @param int        $line
     * @param int|null   $before
     * @param int|null   $after
     *
     * @return array
     */
    protected function filter(Generator $transformed, $line, $before = null, $after = null)
    {
        $location = null;
        $tokens = [];

        foreach ($transformed as $index => $token) {
            if ($location === null && $token['line'] >= $line) {
                $location = $index;
            }

            if ($location !== null && $after !== null && $index > $location + $after) {
                break; // if we're too far through, we're done
            }

            $tokens[] = $token;
        }

        return $before !== null ? array_splice($tokens, max($location - $before, 0)) : $tokens;
    }
}
