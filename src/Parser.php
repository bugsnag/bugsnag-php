<?php

namespace Bugsnag;

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
        $tokens = token_get_all($content);

        $transformed = $this->transform($tokens);

        return $this->filter($transformed, $start, $end);
    }

    /**
     * Transform the given set of tokens into a friendly representation.
     *
     * @param array[] $tokens
     *
     * @return \Generator
     */
    protected function transform($tokens)
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
     * @param array[]  $tokens
     * @param int      $start
     * @param int|null $end
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
