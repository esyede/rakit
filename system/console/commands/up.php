<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Maintenance;

class Up extends Command
{
    /**
     * Bring the application out of maintenance mode.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        if (! Maintenance::active()) {
            echo $this->info('The application is not in maintenance mode.');
            return;
        }

        Maintenance::up();
        echo $this->info('The application is now live.');
    }
}
