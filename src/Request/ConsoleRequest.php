<?php

namespace Bugsnag\Request;

class ConsoleRequest implements RequestInterface
{
    /**
     * The unformated console command.
     *
     * @var string[]
     */
    protected $command;

    /**
     * Create a new console request instance.
     *
     * @param string[] $command an array of the console command input
     *
     * @return void
     */
    public function __construct(array $command)
    {
        $this->command = $command;
    }

    /**
     * Are we currently processing a request?
     *
     * @return bool
     */
    public function isRequest()
    {
        return false;
    }

    /**
     * Get the session data.
     *
     * @return array
     */
    public function getSession()
    {
        return [];
    }

    /**
     * Get the cookies.
     *
     * @return array
     */
    public function getCookies()
    {
        return [];
    }

    /**
     * Get the request formatted as meta data.
     *
     * @return array
     */
    public function getMetaData()
    {
        if (is_array($this->command)) {
            $commandString = implode(' ', $this->command);
            $primaryCommand = $this->command[0];
            $arguments = [];
            $options = [];
            foreach (array_slice($this->command, 1) as $arg) {
                $arg[0] == '-' ? $options[] = $arg : $arguments[] = $arg;
            }
            $data = [
                'Input' => $commandString,
                'Command' => $primaryCommand,
                'Arguments' => $arguments,
                'Options' => $options,
            ];
        } else {
            $data = $this->command;
        }

        return ['console' => $data];
    }

    /**
     * Get the request context.
     *
     * @return string|null
     */
    public function getContext()
    {
        return implode(' ', array_slice($this->command, 0, 2));
    }

    /**
     * Get the request user id.
     *
     * @return string|null
     */
    public function getUserId()
    {
    }
}
