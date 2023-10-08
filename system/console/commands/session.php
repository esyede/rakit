<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Container;
use System\Storage;
use System\Config;
use System\Session as BaseSession;
use System\Session\Drivers\Sweeper;

class Session extends Command
{
    /**
     * Buat tabel session di database.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function table(array $arguments = [])
    {
        $make = Container::resolve('command: make');

        $migration = $make->migration(['create_sessions_table']);
        $stub = __DIR__ . DS . 'stubs' . DS . 'session.stub';

        Storage::put($migration, Storage::get($stub));

        echo PHP_EOL;
    }

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

        echo 'The session table has been swept!' . PHP_EOL;
    }
}
