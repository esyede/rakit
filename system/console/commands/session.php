<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database as DB;
use System\Session as BaseSession;

class Session extends Command
{
    /**
     * Bersihkan session database yang telah kedaluwarsa.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function gc(array $arguments = [])
    {
        $driver = Config::get('session.driver');

        if ('database' === $driver) {
            $lifetime = Config::get('session.lifetime');
            DB::table(Config::get('session.table'))->where('last_activity', '<', time() - ($lifetime * 60))->delete();
        }

        echo $this->info('The session table has been swept!');
    }
}
