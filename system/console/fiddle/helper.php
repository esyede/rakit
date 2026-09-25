<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Helper
{
    /**
     * Accept the REPL object and perform any setup necessary from the CLI flags.
     *
     * @param Fiddle $fiddle
     * @param array  $arguments
     */
    public function handle(Fiddle $fiddle, array $arguments = [])
    {
        if (has_cli_flag('h') || has_cli_flag('help')) {
            $this->usage();
        }

        if (has_cli_flag('v') || has_cli_flag('version')) {
            printf("Fiddle %s\n", RAKIT_VERSION);
            exit(0);
        }

        $require = get_cli_option('require');
        $require = is_null($require) ? [] : explode(',', (string) $require);

        foreach ($arguments as $index => $argument) {
            $argument = (string) $argument;

            if (0 === strpos($argument, '-r') || 0 === strpos($argument, '--require')) {
                if (false !== $position = strpos($argument, '=')) {
                    $require = array_merge($require, explode(',', substr($argument, $position + 1)));
                } elseif (isset($arguments[$index + 1])) {
                    $require = array_merge($require, explode(',', (string) $arguments[$index + 1]));
                }
            }
        }

        if (! empty($require)) {
            $fiddle->starting(function ($worker, $scope) use ($require) {
                foreach ($require as $path) {
                    require $path;
                }

                $worker->set(get_defined_vars());
            });
        }
    }

    /**
     * Print the fiddle usage message and leave.
     */
    private function usage()
    {
        echo <<<'USAGE'
Usage: php rakit fiddle [options]
Fiddle - the rakit REPL console (framework booted, aliases & helpers active)

Options:
-h, --help            show this help message and exit
-r, --require=FILE    comma-separated files to require on startup
-v, --version         show Fiddle version
    --database=NAME   use the given database connection (global option)

Shortcuts:
  Tab                  complete class, model, function, and Class::method names
  Ctrl+R               search the command history
  Ctrl+C               clear the buffer of an unfinished statement (does not quit)
  Ctrl+D               leave the console
  exit; or quit;       leave the console

Mode:
  fork                 statement isolation (pcntl/posix available)
  inline               single process (Windows / no pcntl); no fatal error
                       recovery, Ctrl+C cannot abort a running statement

USAGE;
        exit(0);
    }
}
