<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

class Clear extends Command
{
    /**
     * Bersihkan seluruh cache data.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function cache(array $arguments = [])
    {
        \System\Cache::flush();
        echo $this->info('Cache data has been cleared.');
    }

    /**
     * Bersihkan seluruh file cache views.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function views(array $arguments = [])
    {
        $files = glob(path('storage') . 'views' . DS . '*.bc.php');

        if (is_array($files) && count($files) > 0) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        echo $this->info('View cache files cleared successfully.');
    }

    /**
     * Bersihkan seluruh file log.
     *
     * @return void
     */
    public function logs(array $arguments = [])
    {
        $files = glob(path('storage') . 'logs' . DS . '*');

        if (is_array($files) && count($files) > 0) {
            $preserves = [
                '.gitignore',
                '.htaccess',
                'index.html',
                'index.php',
            ];

            foreach ($files as $file) {
                if (!in_array(basename((string) $file), $preserves)) {
                    @unlink($file);
                }
            }
        }

        echo $this->info('Log files cleared successfully.');
    }
}
