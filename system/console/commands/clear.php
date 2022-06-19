<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

class Clear extends Command
{
    /**
     * Bersihkan seluruh file cache blade.
     *
     * @return void
     */
    public function blade(array $arguments = [])
    {
        $files = glob(path('storage').'views'.DS.'*.bc.php');

        if (is_array($files) && count($files) > 0) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        echo 'Blade cache files cleared successfully.'.PHP_EOL;
    }

    /**
     * Bersihkan seluruh file log.
     *
     * @return void
     */
    public function logs(array $arguments = [])
    {
        $files = glob(path('storage').'logs'.DS.'*');

        if (is_array($files) && count($files) > 0) {
            $preserves = [
                '.gitignore',
                '.htaccess',
                'index.html',
                'index.php',
            ];

            foreach ($files as $file) {
                if (! in_array(basename($file), $preserves)) {
                    @unlink($file);
                }
            }
        }

        echo 'Log files cleared successfully.'.PHP_EOL;
    }
}
