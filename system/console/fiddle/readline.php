<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Readline
{
    /**
     * The socket to use for communication.
     *
     * @var resource
     */
    private $socket;

    /**
     * Whether to clear the buffer on exit.
     *
     * @var bool
     */
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
        if (function_exists('readline_read_history')) {
            /** @disregard */
            readline_read_history($history);
        }

        declare(ticks = 1);
        /** @disregard */
        pcntl_signal(SIGCHLD, SIG_IGN);
        /** @disregard */
        pcntl_signal(SIGINT, [$this, 'clear'], true);

        if (function_exists('readline_completion_function')) {
            readline_completion_function([$this, 'complete']);
        }

        // Wait for the worker to finish executing hooks
        if (fread($this->socket, 1) != Evaluator::READY) {
            throw new \Exception('Evaluator failed to start');
        }

        $parser = new Parser();
        $buf = '';
        $lineno = 1;

        for (;;) {
            $this->clear = false;
            $line = $this->read(sprintf(
                '[%d] %s',
                $lineno,
                (($buf === '') ? $prompt : str_pad('*> ', strlen($prompt), ' ', STR_PAD_LEFT))
            ));

            if ($this->clear) {
                $buf = '';
                continue;
            }

            if (false === $line) {
                $buf = 'exit(0);'; // Ctrl+D acts like exit
            }

            if (is_string($line) && strlen($line) > 0) {
                if (function_exists('readline_add_history')) {
                    /** @disregard */
                    readline_add_history($line);
                }
            }

            $shorthands = ['exit' => 'exit;', 'quit' => 'quit;', 'help' => 'help();'];
            $trimmed = is_string($line) ? rtrim(trim($line), ';') : '';

            if (isset($shorthands[$trimmed])) {
                $line = $shorthands[$trimmed];
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

                        if ($status === Evaluator::EXITED) {
                            if (function_exists('readline_write_history')) {
                                /** @disregard */
                                readline_write_history($history);
                            }

                            echo "\n";
                            exit(0);
                        } elseif ($status === Evaluator::FAILED) {
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Read a single line of input, falling back to plain stream reading.
     *
     * @param string $prompt
     *
     * @return string|false
     */
    private function read($prompt)
    {
        if (function_exists('readline')) {
            return readline($prompt);
        }

        echo $prompt;
        $line = fgets(STDIN);

        return false === $line ? false : rtrim($line, "\r\n");
    }

    /**
     * Tab-completion candidates for the word under the cursor.
     *
     * @param string $input
     * @param int    $index
     *
     * @return array
     */
    public function complete($input, $index)
    {
        $line = (string) readline_info('line_buffer');
        $input = (string) $input;

        if (preg_match('/([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)::[A-Za-z0-9_]*$/', $line, $match)) {
            return $this->complete_methods($match[1], $input);
        }

        return $this->complete_names($input);
    }

    /**
     * Class, alias, model, and function names matching the given prefix.
     *
     * @param string $prefix
     *
     * @return array
     */
    private function complete_names($prefix)
    {
        $functions = get_defined_functions();
        $folders = ['models', 'controllers', 'libraries', 'commands', 'jobs'];
        $names = array_merge(
            array_keys(\System\Autoloader::$aliases),
            get_declared_classes(),
            $functions['user'],
            $functions['internal']
        );

        foreach ($folders as $folder) {
            foreach (glob(path('app').$folder.DS.'*.php') as $file) {
                $names[] = ucfirst(basename($file, '.php'));
            }
        }

        return $this->filter_prefix(array_unique($names), $prefix);
    }

    /**
     * Public methods of the given class matching the given prefix.
     *
     * @param string $class
     * @param string $prefix
     *
     * @return array
     */
    private function complete_methods($class, $prefix)
    {
        $class = ltrim($class, '\\');

        if (! class_exists($class)) {
            return [];
        }

        return $this->filter_prefix(get_class_methods($class), $prefix);
    }

    /**
     * Keep the candidates matching the prefix, sorted.
     *
     * @param array  $names
     * @param string $prefix
     *
     * @return array
     */
    private function filter_prefix($names, $prefix)
    {
        $names = array_values(array_filter($names, function ($name) use ($prefix) {
            return '' === $prefix || 0 === stripos($name, $prefix);
        }));

        sort($names);

        return $names;
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
