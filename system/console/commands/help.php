<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Storage;

class Help extends Command
{
    /**
     * List seluruh command bawaan rakit.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        echo 'Rakit Console v' . RAKIT_VERSION . PHP_EOL;
        echo PHP_EOL;

        echo 'Usage:' . PHP_EOL;
        echo '  command [options] [arguments]' . PHP_EOL;
        echo PHP_EOL;

        $options = json_decode(Storage::get(__DIR__ . DS . 'options.json'));
        $commands = json_decode(Storage::get(__DIR__ . DS . 'commands.json'));

        echo 'Options:' . PHP_EOL;

        foreach ($options as $option => $data) {
            echo '  ' . str_pad($option, 20) . str_pad($data->description, 30) . PHP_EOL;
        }

        echo PHP_EOL;

        echo 'Available Commands:' . PHP_EOL;

        foreach ($commands as $category => $commands) {
            echo PHP_EOL . $category . PHP_EOL;

            foreach ($commands as $heading => $data) {
                echo '  ' . str_pad($heading, 20) . str_pad($data->description, 30) . PHP_EOL;
            }
        }
    }
}
