<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database as DB;

class Session extends Command
{
    /**
     * Clear expired session data from the database.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function gc(array $arguments = [])
    {
        $driver = Config::get('session.driver');

        if ('database' !== $driver) {
            echo $this->warning(sprintf(
                'Nothing to sweep: the session driver is \'%s\', not \'database\'.',
                (string) $driver
            ));

            return;
        }

        DB::table(Config::get('session.table'))
            ->where('last_activity', '<', time() - (Config::get('session.lifetime') * 60))
            ->delete();

        echo $this->info('The session table has been swept!');
    }
}
