<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

use System\Container;
use System\File;
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
    public function table($arguments = [])
    {
        $migrator = Container::resolve('command: migrate');
        $key = Container::resolve('command: key');

        if (is_null(trim(Config::get('application.key', null)))) {
            $key->generate();
        }

        $migration = $migrator->make(['create_session_table']);
        $stub = __DIR__.DS.'stubs'.DS.'session.stub';

        File::put($migration, File::get($stub));

        $this->driver('database');

        echo PHP_EOL;

        $migrator->run();
    }

    /**
     * Bersihkan session yang telah kedaluwarsa.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function sweep($arguments = [])
    {
        $driver = BaseSession::factory(Config::get('session.driver'));

        if ($driver instanceof Sweeper) {
            $lifetime = Config::get('session.lifetime');
            $driver->sweep(time() - ($lifetime * 60));
        }

        echo 'The session table has been swept!';
    }

    /**
     * Ubah driver session di file konfigurasi.
     *
     * @param string $driver
     *
     * @return void
     */
    protected function driver($driver)
    {
        $config = File::get(path('app').'config'.DS.'session.php');

        $pattern = "/(('|\")driver('|\"))\h*=>\h*(\'|\")\s?(\'|\")?.*/i";
        $replaced = preg_replace($pattern, "'driver' => '{$driver}',", $config);

        if (! is_null($replaced)) {
            File::put(path('app').'config'.DS.'session.php', $replaced);
        }
    }
}
