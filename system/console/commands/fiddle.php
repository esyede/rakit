<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

class Fiddle extends Command
{
    /**
     * Start the REPL (Read-Eval-Print Loop) console.
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        if (!function_exists('pcntl_signal')) {
            echo $this->error("The PCNTL support seems to be missing or disabled.\n");
            exit(1);
        }

        $fiddle = new \System\Console\Fiddle\Fiddle();
        $helper = new \System\Console\Fiddle\Helper();
        $helper->handle($fiddle);
        $fiddle->start();
    }
}
