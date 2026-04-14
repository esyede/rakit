<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Evaluator
{
    const FATAL = 255;
    const DONE = "\0";
    const EXITED = "\1";
    const FAILED = "\2";
    const READY = "\3";

    private $socket;
    private $exports = [];
    private $starting = [];
    private $failing = [];
    private $prev_pid;
    private $pid;
    private $aborted;
    private $inspector;
    private $exceptor;

    /**
     * Create a new worker using the given socket for communication.
     *
     * @param resource $socket
     */
    public function __construct($socket)
    {
        $this->socket = $socket;
        $this->inspector = new Dumper();
        stream_set_blocking($socket, 0);
    }

    /**
     * Set local variables to be placed in the workers's scope.
     *
     * @param array|string $name
     * @param mixed        $value
     */
    public function set($name, $value = null)
    {
        $this->exports = array_merge($this->exports, is_array($name) ? $name : [$name => $value]);
    }

    /**
     * Set hooks to run inside the worker before it starts looping.
     *
     * @param array $hooks
     */
    public function starting($hooks)
    {
        $this->starting = $hooks;
    }

    /**
     * Set hooks to run inside the worker after a fatal error is caught.
     *
     * @param array $hooks
     */
    public function failing($hooks)
    {
        $this->failing = $hooks;
    }

    /**
     * Set an Inspector object for Repl to output return values with.
     *
     * @param object $inspector any object the responds to inspect($v)
     */
    public function inspector($inspector)
    {
        $this->inspector = $inspector;
    }

    /**
     * Start the worker.
     *
     * This method never returns.
     */
    public function start()
    {
        $scope = $this->run_hook($this->starting);
        extract($scope);

        $this->write($this->socket, self::READY);

        /* Note the naming of the local variables due to shared scope with the user here */
        for (;;) {
            declare (ticks = 1);
            /** @disregard */
            pcntl_signal(SIGINT, SIG_IGN, true); // Do not exit on Ctrl+C
            $this->aborted = false;
            $input = $this->transform($this->read($this->socket));

            if ($input === null) {
                continue;
            }

            $response = self::DONE;
            /** @disregard */
            $this->prev_pid = posix_getpid();
            /** @disregard */
            $this->pid  = pcntl_fork();

            if ($this->pid < 0) {
                throw new \RuntimeException('Failed to fork child labourer');
            } elseif ($this->pid > 0) {
                /** @disregard */
                pcntl_signal(SIGINT, [$this, 'abort'], true); // Kill child on Ctrl+C
                /** @disregard */
                pcntl_waitpid($this->pid, $status);

                if (!$this->aborted && $status != (self::FATAL << 8)) {
                    $response = self::EXITED;
                } else {
                    $this->run_hook($this->failing);
                    $response = self::FAILED;
                }
            } else {
                // If the user has installed a custom exception handler, install a new
                // one which calls it and then (if the custom handler didn't already exit) exits with the correct status.
                // If not, leave the exception handler unset; we'll display an uncaught exception error and carry on.
                $oldexh = set_exception_handler([$this, 'exception_handler']);

                if ($oldexh && !$this->exceptor) {
                    $this->exceptor = $oldexh; // Save the old handler (once)
                } else {
                    restore_exception_handler();
                }

                /** @disregard */
                pcntl_signal(SIGINT, SIG_DFL, true); // Allow user code to handle ctrl-c if it wants to
                /** @disregard */
                $pid = posix_getpid();
                $result = eval($input);

                /** @disregard */
                if (posix_getpid() != $pid) {
                    // Whatever the user entered caused a forked child
                    // (totally valid, but we don't want that child to loop and wait for input)
                    exit(0);
                }

                if (preg_match('/\s*return\b/i', $input)) {
                    fwrite(STDOUT, sprintf("%s\n", $this->inspector->inspect($result)));
                }

                $this->kill_previous();
            }

            $this->write($this->socket, $response);

            if ($response == self::EXITED) {
                exit(0);
            }
        }
    }

    /**
     * While a child process is running, terminate it immediately.
     */
    public function abort()
    {
        printf("Aborting...\n");
        $this->aborted = true;
        /** @disregard */
        posix_kill($this->pid, SIGKILL);
        /** @disregard */
        pcntl_signal_dispatch();
    }

    /**
     * Call the user-defined exception handler, then exit correctly.
     */
    public function exception_handler($ex)
    {
        call_user_func($this->exceptor, $ex);
        exit(self::FATAL);
    }

    private function run_hook($hooks)
    {
        extract($this->exports);

        foreach ($hooks as $hook) {
            if (is_string($hook)) {
                eval($hook);
            } elseif (is_callable($hook)) {
                call_user_func($hook, $this, get_defined_vars());
            } else {
                throw new \Exception(sprintf('Hooks must be closures or strings of PHP code. Got [%s].', gettype($hook)));
            }

            extract($this->exports);
        }

        return get_defined_vars();
    }

    private function kill_previous()
    {
        /** @disregard */
        posix_kill($this->prev_pid, SIGTERM);
        /** @disregard */
        pcntl_signal_dispatch();
    }

    private function write($socket, $data)
    {
        if (!fwrite($socket, $data)) {
            throw new \Exception('Socket error: failed to write data');
        }
    }

    private function read($socket)
    {
        $read = [$socket];
        $except = [$socket];

        if ($this->select($read, $except) > 0) {
            if ($read) {
                return stream_get_contents($read[0]);
            } else if ($except) {
                throw new \Exception("Socket error: closed");
            }
        }
    }

    private function select(&$read, &$except)
    {
        $write = null;
        set_error_handler(function () {
            return true;
        }, E_WARNING);
        $result = stream_select($read, $write, $except, 10);
        restore_error_handler();
        return $result;
    }

    private function transform($input)
    {
        if ($input === null) {
            return null;
        }

        $transforms = ['exit' => 'exit(0)'];

        foreach ($transforms as $from => $to) {
            $input = preg_replace('/^\s*' . preg_quote($from, '/') . '\s*;?\s*$/', $to . ';', $input);
        }

        return $input;
    }
}
