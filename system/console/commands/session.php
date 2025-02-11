<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Session as BaseSession;
use System\Session\Drivers\Sweeper;

class Session extends Command
{
    /**
     * Bersihkan session yang telah kedaluwarsa.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function sweep(array $arguments = [])
    {
        $driver = BaseSession::factory(Config::get('session.driver'));

        if ($driver instanceof Sweeper) {
            $lifetime = Config::get('session.lifetime');
            $driver->sweep(time() - ($lifetime * 60));
        }

        echo $this->info('The session table has been swept!');
    }
}
