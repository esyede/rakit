<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Autoloader;

class Optimize extends Command
{
    /**
     * Generate optimized classmap untuk production.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        return $this->classmap($arguments);
    }

    /**
     * Generate optimized classmap untuk production.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function classmap(array $arguments = [])
    {
        echo $this->info('Generating optimized classmap...');

        $directories = [
            path('system'),
            path('app') . 'models',
            path('app') . 'controllers',
            path('app') . 'libraries',
            path('app') . 'commands',
        ];

        if (is_dir($package_path = path('package'))) {
            $packages = scandir($package_path);

            foreach ($packages as $package) {
                if ($package !== '.' && $package !== '..' && is_dir($package_path . $package)) {
                    $directories[] = $package_path . $package;
                }
            }
        }

        $classmap = Autoloader::generate_classmap($directories);

        echo $this->info('');
        echo $this->info('Classmap generated successfully!');
        echo $this->info('File: ' . path('storage') . 'classmap.php');
        echo $this->info('Total classes mapped: ' . count($classmap));
        echo $this->info('');
        echo $this->warning('To use the classmap, add this to your bootstrap:');
        echo $this->warning('    Autoloader::load_classmap();');
    }

    /**
     * Test load classmap yang sudah di-generate.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function test(array $arguments = [])
    {
        if (!is_file(path('storage') . 'classmap.php')) {
            echo $this->error('Classmap file not found!');
            echo $this->warning('Run: php rakit optimize');
            return;
        }

        echo $this->info('Loading classmap...');

        $loaded = Autoloader::load_classmap(path('storage') . 'classmap.php');

        if ($loaded) {
            $stats = Autoloader::get_stats();
            echo $this->info('Classmap loaded successfully!');
            echo $this->info('Total mappings: ' . $stats['mappings']);
            echo $this->info('Total namespaces: ' . $stats['namespaces']);
            echo $this->info('Total directories: ' . $stats['directories']);
        } else {
            echo $this->error('Failed to load classmap!');
        }
    }

    /**
     * Tampilkan statistik autoloader.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function stats(array $arguments = [])
    {
        $stats = Autoloader::get_stats();

        echo $this->info('Autoloader Statistics:');
        echo $this->info('  Loaded files    : ' . $stats['loaded_files']);
        echo $this->info('  Class mappings  : ' . $stats['mappings']);
        echo $this->info('  Namespaces      : ' . $stats['namespaces']);
        echo $this->info('  PSR directories : ' . $stats['directories']);
        echo $this->info('  Aliases         : ' . $stats['aliases']);

        if (is_file(path('storage') . 'classmap.php')) {
            $size = filesize(path('storage') . 'classmap.php');
            $size_kb = round($size / 1024, 2);
            echo $this->info('');
            echo $this->info('Classmap file   : ' . path('storage') . 'classmap.php');
            echo $this->info('File size       : ' . $size_kb . ' KB');
        }
    }

    /**
     * Generate classmap untuk direktori spesifik.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function custom(array $arguments = [])
    {
        if (empty($arguments)) {
            echo $this->error('Directory path required!');
            echo $this->warning('Usage: php rakit optimize:custom /path/to/directory');
            return;
        }

        $directory = $arguments[0];

        if (!is_dir($directory)) {
            echo $this->error('Directory not found: ' . $directory);
            return;
        }

        echo $this->info('Generating classmap for: ' . $directory);

        $classmap = Autoloader::generate_classmap([$directory]);

        echo $this->info('');
        echo $this->info('Custom classmap generated!');
        echo $this->info('File: ' . path('storage') . 'classmap.php');
        echo $this->info('Total classes: ' . count($classmap));
    }
}
