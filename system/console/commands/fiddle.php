<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Console\Color;
use System\Hook;

class Fiddle extends Command
{
    /**
     * Start the REPL (Read-Eval-Print Loop) console.
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        $isolated = function_exists('pcntl_fork') && function_exists('posix_kill');

        require path('system').'console'.DS.'fiddle'.DS.'builtin.php';

        echo $this->info(sprintf(
            'Rakit %s | PHP %s | db: %s | mode: %s',
            RAKIT_VERSION,
            PHP_VERSION,
            Config::get('database.default'),
            $isolated ? 'fork' : 'inline'
        ));
        echo $this->warning('Type help() for help. Leave with: exit; / quit; / Ctrl+D.');

        $fiddle = new \System\Console\Fiddle\Fiddle();
        $helper = new \System\Console\Fiddle\Helper();
        $helper->handle($fiddle, $arguments);

        Hook::listen('rakit.query', function ($sql, $bindings, $time) {
            echo Color::cyan(sprintf('-- %s (%s ms)', $sql, $time));
        });

        $fiddle->start();
    }
}
