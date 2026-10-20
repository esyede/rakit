<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Maintenance;

class Down extends Command
{
    /**
     * Put the application in maintenance mode.
     *
     * Options: --secret= (a URI that lets its visitor through), --retry= (seconds,
     * for Retry-After), --allow= (comma-separated client IPs let through).
     *
     * @param array $arguments
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        $secret = get_cli_option('secret');
        $retry = get_cli_option('retry');
        $allow = get_cli_option('allow');

        Maintenance::down($secret, is_null($retry) ? null : (int) $retry, $allow ? explode(',', $allow) : []);

        echo $this->warning('The application is now in maintenance mode.');

        if ($secret) {
            echo $this->info('Bypass it by visiting /'.ltrim($secret, '/').' on the site.');
        }
    }
}
