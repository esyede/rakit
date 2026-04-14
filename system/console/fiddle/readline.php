<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Readline
{
    private $socket;
    private $clear = false;

    /**
     * Create a new Readline using $socket for communication.
     *
     * @param resource $socket
     */
    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    /**
     * Start the client with an prompt and readline history path.
     *
     * @param string $prompt
     * @param string $history
     */
    public function start($prompt, $history)
    {
        readline_read_history($history);

        declare (ticks = 1);
        /** @disregard */
        pcntl_signal(SIGCHLD, SIG_IGN);
        /** @disregard */
        pcntl_signal(SIGINT, [$this, 'clear'], true);

        // Wait for the worker to finish executing hooks
        if (fread($this->socket, 1) != Evaluator::READY) {
            throw new \Exception('Evaluator failed to start');
        }

        $parser = new Parser();
        $buf = '';
        $lineno = 1;

        for (;;) {
            $this->clear = false;
            $line = readline(sprintf('[%d] %s', $lineno, ($buf == '' ? $prompt : str_pad('*> ', strlen($prompt), ' ', STR_PAD_LEFT))));

            if ($this->clear) {
                $buf = '';
                continue;
            }

            if (false === $line) {
                $buf = 'exit(0);'; // Ctrl+D acts like exit
            }

            if (strlen($line) > 0) {
                /** @disregard */
                readline_add_history($line);
            }

            $buf .= sprintf("%s\n", $line);

            if ($statements = $parser->statements($buf)) {
                ++$lineno;
                $buf = '';

                foreach ($statements as $stmt) {
                    if (false === $written = fwrite($this->socket, $stmt)) {
                        throw new \Exception('Socket error: failed to write data');
                    }

                    if ($written > 0) {
                        $status = fread($this->socket, 1);

                        if ($status == Evaluator::EXITED) {
                            /** @disregard */
                            readline_write_history($history);
                            echo "\n";
                            exit(0);
                        } elseif ($status == Evaluator::FAILED) {
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Clear the input buffer.
     */
    public function clear()
    {
        // FIXME: I'd love to have this send \r to readline so it puts the user on a blank line
        $this->clear = true;
    }
}
