<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Session as Store;
use System\Session\Drivers\Sweeper;

class Session extends Command
{
    /**
     * Delete expired sessions from storage.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function gc(array $arguments = [])
    {
        $name = Config::get('session.driver');
        $driver = Store::factory($name);

        if (! ($driver instanceof Sweeper)) {
            echo $this->warning(sprintf(
                'Nothing to sweep: the \'%s\' driver expires its own data.',
                (string) $name
            ));

            return;
        }

        $driver->sweep(time() - (Config::get('session.lifetime') * 60));

        echo $this->info(sprintf('Expired sessions swept from the \'%s\' driver.', (string) $name));
    }
}
