<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Container;
use System\Storage;

class Cache extends Command
{
    /**
     * Buat tabel cache di database.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function table(array $arguments = [])
    {
        $make = Container::resolve('command: make');

        $migration = $make->migration(['create_caches_table']);
        $stub = __DIR__ . DS . 'stubs' . DS . 'cache.stub';

        Storage::put($migration, Storage::get($stub));

        echo PHP_EOL;
    }
}
