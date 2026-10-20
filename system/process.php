<?php

namespace System;

defined('DS') or exit('No direct access.');

class Process
{
    /**
     * The command line.
     *
     * @var string
     */
    protected $command;

    /**
     * Whether the command is one program with its arguments, not a shell line.
     *
     * @var bool
     */
    protected $program;

    /**
     * The options: path, env, input, timeout.
     *
     * @var array
     */
    protected $options;

    /**
     * Called with ('out' or 'err', $chunk) as output arrives.
     *
     * @var \Closure|null
     */
    protected $listener;

    /**
     * The proc_open() handle.
     *
     * @var resource|null
     */
    protected $handle;

    /**
     * The stdout and stderr pipes still open.
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * When the process started.
     *
     * @var float
     */
    protected $started;

    /**
     * The standard output so far.
     *
     * @var string
     */
    protected $output = '';

    /**
     * The error output so far.
     *
     * @var string
     */
    protected $error_output = '';

    /**
     * The exit code, NULL while running.
     *
     * @var int|null
     */
    protected $exit_code;

    /**
     * Constructor.
     *
     * @param string|array  $command
     * @param array         $options
     * @param \Closure|null $output
     */
    public function __construct($command, array $options = [], $output = null)
    {
        $this->program = is_array($command);
        $this->command = $this->program ? implode(' ', array_map('escapeshellarg', $command)) : (string) $command;
        $this->options = array_merge(['path' => null, 'env' => null, 'input' => null, 'timeout' => 60], $options);
        $this->listener = ($output instanceof \Closure) ? $output : null;
    }

    /**
     * Run a command and wait for it to end.
     *
     *     $result = Process::run(['git', 'log', '-1'], ['path' => path('base')]);
     *     $result->successful() && print $result->output();
     *
     * @param string|array  $command
     * @param array         $options
     * @param \Closure|null $output
     *
     * @return Process
     */
    public static function run($command, array $options = [], $output = null)
    {
        return static::start($command, $options, $output)->wait();
    }

    /**
     * Start a command without waiting: check running(), read output(), then wait().
     *
     * @param string|array  $command
     * @param array         $options
     * @param \Closure|null $output
     *
     * @return Process
     */
    public static function start($command, array $options = [], $output = null)
    {
        $process = new static($command, $options, $output);
        $process->open();

        return $process;
    }

    /**
     * Wait for the process to end.
     *
     * @return $this
     *
     * @throws \RuntimeException when it runs past its timeout (it is stopped first)
     */
    public function wait()
    {
        while ($this->running()) {
            $this->read(0.1);
            $this->check_timeout();
        }

        $this->read(0);

        return $this;
    }

    /**
     * Check whether the process still runs.
     *
     * @return bool
     */
    public function running()
    {
        if (! is_null($this->exit_code) || ! is_resource($this->handle)) {
            return false;
        }

        $status = proc_get_status($this->handle);

        if ($status['running']) {
            return true;
        }

        $this->exit_code = (int) $status['exitcode'];
        $this->read(0);
        $this->close();

        return false;
    }

    /**
     * Stop the process: SIGTERM, then SIGKILL when it is still alive after $grace seconds.
     *
     * @param int $grace
     *
     * @return $this
     */
    public function stop($grace = 2)
    {
        if (! $this->running()) {
            return $this;
        }

        proc_terminate($this->handle);
        $until = microtime(true) + $grace;

        while ($this->running() && microtime(true) < $until) {
            usleep(20000);
        }

        if ($this->running()) {
            proc_terminate($this->handle, defined('SIGKILL') ? SIGKILL : 9);

            while ($this->running()) {
                usleep(20000);
            }
        }

        return $this;
    }

    /**
     * Get the standard output so far.
     *
     * @return string
     */
    public function output()
    {
        $this->read(0);
        return $this->output;
    }

    /**
     * Get the error output so far.
     *
     * @return string
     */
    public function error_output()
    {
        $this->read(0);
        return $this->error_output;
    }

    /**
     * Get the exit code, NULL while running.
     *
     * @return int|null
     */
    public function exit_code()
    {
        $this->running();
        return $this->exit_code;
    }

    /**
     * Check whether the process ended with exit code 0.
     *
     * @return bool
     */
    public function successful()
    {
        return 0 === $this->exit_code();
    }

    /**
     * Check whether the process ended with an error.
     *
     * @return bool
     */
    public function failed()
    {
        $code = $this->exit_code();
        return ! is_null($code) && 0 !== $code;
    }

    /**
     * Throw when the process failed.
     *
     * @return $this
     *
     * @throws \RuntimeException
     */
    public function ensure_successful()
    {
        if ($this->failed()) {
            throw new \RuntimeException(sprintf(
                'The command "%s" failed with exit code %d.%s',
                $this->command,
                $this->exit_code,
                ('' === trim($this->error_output)) ? '' : ' '.trim($this->error_output)
            ));
        }

        return $this;
    }

    /**
     * Get the command line.
     *
     * @return string
     */
    public function command()
    {
        return $this->command;
    }

    /**
     * Start the process.
     */
    protected function open()
    {
        $command = ($this->program && '\\' !== DIRECTORY_SEPARATOR) ? 'exec '.$this->command : $this->command;
        $env = is_null($this->options['env']) ? null : array_merge(static::environment(), (array) $this->options['env']);
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $this->handle = proc_open($command, $descriptors, $pipes, $this->options['path'], $env);

        if (! is_resource($this->handle)) {
            throw new \RuntimeException(sprintf('Unable to start the command: %s', $this->command));
        }

        $this->started = microtime(true);

        if (! is_null($this->options['input'])) {
            fwrite($pipes[0], (string) $this->options['input']);
        }

        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $this->pipes = [1 => $pipes[1], 2 => $pipes[2]];
    }

    /**
     * Read what the pipes hold, waiting up to $seconds for something to arrive.
     *
     * @param float $seconds
     */
    protected function read($seconds)
    {
        if (empty($this->pipes)) {
            return;
        }

        $read = array_values($this->pipes);
        $write = $except = null;

        if (false === @stream_select($read, $write, $except, 0, (int) ($seconds * 1000000))) {
            return;
        }

        foreach ($this->pipes as $type => $pipe) {
            while (is_resource($pipe) && '' !== ($chunk = (string) fread($pipe, 65536))) {
                if (1 === $type) {
                    $this->output .= $chunk;
                } else {
                    $this->error_output .= $chunk;
                }

                if ($this->listener) {
                    call_user_func($this->listener, (1 === $type) ? 'out' : 'err', $chunk);
                }
            }

            if (feof($pipe)) {
                fclose($pipe);
                unset($this->pipes[$type]);
            }
        }
    }

    /**
     * Stop and throw when the process runs past its timeout.
     */
    protected function check_timeout()
    {
        $timeout = (float) $this->options['timeout'];

        if ($timeout > 0 && (microtime(true) - $this->started) > $timeout && $this->running()) {
            $this->stop(1);

            throw new \RuntimeException(sprintf('The command "%s" exceeded the timeout of %s seconds.', $this->command, $timeout));
        }
    }

    /**
     * Close the pipes and the handle.
     */
    protected function close()
    {
        foreach ($this->pipes as $pipe) {
            is_resource($pipe) && fclose($pipe);
        }

        $this->pipes = [];

        if (is_resource($this->handle)) {
            proc_close($this->handle);
        }

        $this->handle = null;
    }

    /**
     * Get the current environment variables.
     *
     * @return array
     */
    protected static function environment()
    {
        $env = (PHP_VERSION_ID >= 70100) ? getenv() : array_merge($_SERVER, $_ENV);

        return array_filter((array) $env, function ($value) {
            return is_string($value);
        });
    }

    /**
     * Stop the process when the object goes away while it runs.
     */
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            $this->stop(1);
        }
    }
}
