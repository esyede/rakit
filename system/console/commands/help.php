<?php
namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

use System\Storage;

class Help extends Command
{
    /**
     * List seluruh command bawaan rakit.
     *
     * @return void
     */
    public function run()
    {
        echo 'Rakit Console '.RAKIT_VERSION.PHP_EOL;
        echo PHP_EOL;

        echo 'Usage:'.PHP_EOL;
        echo '  command [options] [arguments]'.PHP_EOL;
        echo PHP_EOL;

        $options_data = json_decode(Storage::get(__DIR__.DS.'options.json'));
        $commands_data = json_decode(Storage::get(__DIR__.DS.'commands.json'));

        echo 'Options:'.PHP_EOL;

        foreach ($options_data as $option => $details) {
            echo '  '.str_pad($option, 20).str_pad($details->description, 30).PHP_EOL;
        }

        echo PHP_EOL;

        echo 'Available Commands:';

        foreach ($commands_data as $category => $commands) {
            echo PHP_EOL.$category.PHP_EOL;

            foreach ($commands as $command => $details) {
                echo '  '.str_pad($command, 20).str_pad($details->description, 30).PHP_EOL;
            }
        }
    }
}
