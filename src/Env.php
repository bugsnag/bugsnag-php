<?php

namespace Bugsnag;

class Env
{
    /**
     * Reads an environment variable from $_ENV, $_SERVER or via getenv().
     *
     * Supports a thread-safe read via the superglobals, but falls back on
     * getenv() to allow for other methods of setting environment data. See
     * this article for more background context:
     * https://mattallan.me/posts/how-php-environment-variables-actually-work/.
     *
     * Copied from phpdotenv:
     * https://github.com/vlucas/phpdotenv/blob/2.6/src/Loader.php#L291.
     *
     * BSD 3-Clause license provided at this bottom of this file.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function get($name)
    {
        switch (true) {
            case array_key_exists($name, $_ENV):
                return $_ENV[$name];

            case array_key_exists($name, $_SERVER):
                return $_SERVER[$name];

            default:
                $value = getenv($name);

                return $value === false ? null : $value; // switch getenv default to null
        }
    }
}

/*
Referring to Env::get() function above:

The BSD 3-Clause License
http://opensource.org/licenses/BSD-3-Clause

Copyright (c) 2013, Vance Lucas
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are
met:

 * Redistributions of source code must retain the above copyright
   notice,
this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in the
documentation and/or other materials provided with the distribution.
 * Neither the name of the Vance Lucas nor the names of its contributors
may be used to endorse or promote products derived from this software
without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
