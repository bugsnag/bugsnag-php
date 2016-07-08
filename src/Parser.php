<?php

namespace Bugsnag;

use Generator;

class Parser
{
    /**
     * Parse the file at the given path.
     *
     * You may optionally specify the region to restrict the result too.
     *
     * @param string   $content
     * @param int      $start
     * @param int|null $end
     *
     * @return \Generator
     */
    public function parse($content, $start = 1, $end = null)
    {
        $tokens = $this->tokenize($content);

        $transformed = $this->transform($tokens);

        return $this->filter($transformed, $start, $end);
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
     * @param \Generator $tokens
     * @param int        $start
     * @param int|null   $end
     *
     * @return \Generator
     */
    protected function filter($tokens, $start, $end)
    {
        foreach ($tokens as $token) {
            if ($token['line'] < $start) {
                continue; // if we're too early in the file, skip
            }

            if ($end && $token['line'] > $end) {
                break; // if we're too far through, we're done
            }

            yield $token;
        }
    }
}
