<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Fiddle
{
    private $prompt;
    private $history;
    private $exports = [];
    private $starting = [];
    private $failing = [];
    private $inspector;

    /**
     * Create a new REPL, which consists of an evaluation worker and a readline client.
     *
     * @param string $prompt
     * @param string $historyFile
     */
    public function __construct($prompt = 'FIDDLE> ')
    {
        $this->prompt($prompt);
        $this->history = path('storage') . 'console' . DS . '.fiddle_history';
        $this->inspector = new Inspector();
    }

    /**
     * Add a new hook to run in the context of the REPL when it starts.
     *
     * @param mixed $hook
     *
     *     $fiddle->starting(function ($worker, $vars) {
     *         $worker->set('date', date('Y-m-d'));
     *     });
     *
     *     $fiddle->starting('echo "The date is $date\n";');
     */
    public function starting($hook)
    {
        $this->starting[] = $hook;
    }

    /**
     * Add a new hook to run in the context of the REPL when a fatal error occurs.
     *
     * @param mixed $hook
     *
     *     $fiddle->failing(function ($worker, $vars) {
     *         DB::pdo()->rollBack();
     *     });
     */
    public function failing($hook)
    {
        $this->failing[] = $hook;
    }

    /**
     * Set a local variable, or many local variables.
     *
     * @param array|string $local
     * @param mixed        $value
     */
    public function set($local, $value = null)
    {
        $this->exports = array_merge($this->exports, is_array($local) ? $local : [$local => $value]);
    }

    /**
     * Sets the Repl prompt text
     *
     * @param string $prompt
     */
    public function prompt($prompt)
    {
        $this->prompt = $prompt;
    }

    /**
     * Set an Inspector object for Repl to output return values with.
     *
     * @param object $inspector
     */
    public function inspector($inspector)
    {
        $this->inspector = $inspector;
    }

    /**
     * Start the REPL (display the readline prompt).
     *
     * This method never returns.
     */
    public function start()
    {
        declare (ticks = 1);
        /** @disregard */
        pcntl_signal(SIGINT, SIG_IGN, true);

        if (!$pipes = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP)) {
            throw new \Exception('Failed to create socket pair');
        }

        /** @disregard */
        $pid = pcntl_fork();

        if ($pid > 0) {
            if (function_exists('setproctitle')) {
                /** @disregard */
                setproctitle('Repl (master)');
            }

            fclose($pipes[0]);
            $client = new Readline($pipes[1]);
            $client->start($this->prompt, $this->history);
        } elseif ($pid < 0) {
            throw new \Exception('Failed to fork child process');
        } else {
            if (function_exists('setproctitle')) {
                /** @disregard */
                setproctitle('Repl (worker)');
            }

            fclose($pipes[1]);

            $worker = new Evaluator($pipes[0]);
            $worker->set($this->exports);
            $worker->starting($this->starting);
            $worker->failing($this->failing);
            $worker->inspector($this->inspector);
            $worker->start();
        }
    }
}
