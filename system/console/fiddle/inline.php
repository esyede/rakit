<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Inline
{
    /**
     * The prompt string to display to the user.
     *
     * @var string
     */
    private $prompt;

    /**
     * The stream to read user input from.
     *
     * @var resource
     */
    private $input;

    /**
     * The history file to store REPL command history.
     *
     * @var string
     */
    private $history;

    /**
     * The inspector to use for inspecting variables in the REPL.
     *
     * @var Inspector
     */
    private $inspector;

    /**
     * The parser to break the input buffer into statements.
     *
     * @var Parser
     */
    private $parser;

    /**
     * The exported variables from the REPL.
     *
     * @var array
     */
    private $exports = [];

    /**
     * The hooks to run in the context of the REPL when it starts.
     *
     * @var array
     */
    private $starting = [];

    /**
     * The unfinished input buffer of multi-line statements.
     *
     * @var string
     */
    private $buffer = '';

    /**
     * The current input line number.
     *
     * @var int
     */
    private $lineno = 1;

    /**
     * Create a single process REPL for platforms without pcntl/posix.
     *
     * @param string      $prompt
     * @param Inspector   $inspector
     */
    public function __construct($prompt = 'FIDDLE> ', $inspector = null)
    {
        $this->prompt = $prompt;
        $this->inspector = $inspector ? $inspector : new Inspector();
        $this->parser = new Parser();
        $this->history = path('storage').'console'.DS.'.fiddle_history';
        $this->input = defined('STDIN') ? STDIN : fopen('php://stdin', 'r');
    }

    /**
     * Set the stream to read user input from.
     *
     * @param resource $stream
     */
    public function input($stream)
    {
        $this->input = $stream;
    }

    /**
     * Set a local variable, or many local variables.
     *
     * @param array|string $local
     * @param mixed        $value
     */
    public function set($local, $value = null)
    {
        $this->exports = array_merge(
            $this->exports,
            is_array($local) ? $local : [$local => $value]
        );
    }

    /**
     * Add hooks to run in the context of the REPL when it starts.
     *
     * @param array|callable|string $hook
     */
    public function starting($hook)
    {
        $items = is_array($hook) ? $hook : [$hook];

        foreach ($items as $item) {
            $this->starting[] = $item;
        }
    }

    /**
     * Start the REPL. This method returns when the user leaves the console.
     */
    public function start()
    {
        extract($this->run_hooks());

        for (;;) {
            $this->write_prompt();
            $line = fgets($this->input);

            if (false === $line) {
                echo PHP_EOL;
                return;
            }

            $line = rtrim($line, "\r\n");
            $this->remember($line);

            $shorthands = ['exit' => 'exit;', 'quit' => 'quit;', 'help' => 'help();'];
            $trimmed = rtrim(trim($line), ';');

            if (isset($shorthands[$trimmed])) {
                $line = $shorthands[$trimmed];
            }

            $this->buffer .= $line."\n";

            if ($statements = $this->parser->statements($this->buffer)) {
                ++$this->lineno;
                $this->buffer = '';

                foreach ($statements as $statement) {
                    if (preg_match('/^\s*(?:exit|quit)\s*;?\s*$/i', $statement)) {
                        echo PHP_EOL;
                        return;
                    }

                    try {
                        $result = eval($statement);
                    } catch (\Throwable $ex) {
                        echo $this->inspector->inspect_error($ex), "\n";
                        continue;
                    } catch (\Exception $ex) {
                        echo $this->inspector->inspect_error($ex), "\n";
                        continue;
                    }

                    if (preg_match('/\s*return\b/i', $statement) && isset($result)) {
                        echo $this->inspector->inspect($result), "\n";
                    }
                }
            }
        }
    }

    /**
     * Run hooks in the REPL scope.
     *
     * @return array
     */
    private function run_hooks()
    {
        extract($this->exports);

        foreach ($this->starting as $hook) {
            if (is_string($hook)) {
                eval($hook);
            } elseif (is_callable($hook)) {
                call_user_func($hook, $this, get_defined_vars());
            }

            extract($this->exports);
        }

        return get_defined_vars();
    }

    /**
     * Write the single line or continuation prompt.
     */
    private function write_prompt()
    {
        echo sprintf('[%d] %s', $this->lineno, ($this->buffer === '')
            ? $this->prompt
            : str_pad('*> ', strlen($this->prompt), ' ', STR_PAD_LEFT));
    }

    /**
     * Remember a typed line when no readline history is available.
     *
     * @param string $line
     */
    private function remember($line)
    {
        if (strlen($line) > 0 && ! function_exists('readline_add_history')) {
            @file_put_contents($this->history, $line.PHP_EOL, FILE_APPEND);
        }
    }
}
