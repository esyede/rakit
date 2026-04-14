<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Helper
{
    /**
     * Accept the REPL object and perform any setup necessary from the CLI flags.
     *
     * @param Fiddle $fiddle
     */
    public function handle(Fiddle $fiddle)
    {
        $args = getopt('hvr:', ['help', 'version', 'require:']);

        foreach ($args as $option => $value) {
            switch ($option) {
                case 'r':
                case 'require':
                    $require = array_reduce((array) $value, function ($acc, $v) {
                        return array_merge($acc, explode(',', $v));
                    }, []);

                    $fiddle->starting(function ($worker, $scope) use ($require) {
                        foreach ($require as $path) {
                            require $path;
                        }

                        $worker->set(get_defined_vars());
                    });
                    break;

                case 'h':
                case 'help':

                    echo <<<USAGE
Usage: repl [options]
repl is a tiny REPL for PHP

Options:
-h, --help     show this help message and exit
-r, --require  a comma-separated list of files to require on startup
-v, --version  show Repl version

USAGE;
                    exit(0);

                case 'v':
                case 'version':
                    printf("Fiddle %s\n", RAKIT_VERSION);
                    exit(0);
            }
        }
    }
}
