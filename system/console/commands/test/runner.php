<?php

namespace System\Console\Commands\Test;

defined('DS') or exit('No direct script access.');

use System\Storage;
use System\Package;
use System\Console\Commands\Command;

class Runner extends Command
{
    /**
     * Base directory tempat test akan dieksekusi.
     * File phpunit.xml juga harus disimpan di direktori ini.
     *
     * @var string
     */
    protected $base;

    /**
     * Jalankan seluruh unit test milik folder application.
     *
     * @param array $packages
     *
     * @return void
     */
    public function run($packages = [])
    {
        $packages = is_array($packages) ? $packages : [$packages];
        $packages = (0 === count($packages)) ? [DEFAULT_PACKAGE] : $packages;
        $this->package($packages);
    }

    /**
     * Jalankan seluruh unit test milik folder system.
     *
     * @return void
     */
    public function core()
    {
        $this->base = path('base').'tests'.DS;
        $this->stub($this->base.'cases');
        $this->kickstart();
    }

    /**
     * Jalankan seluruh unit test milik sebuah paket.
     *
     * @param array $packages
     *
     * @return void
     */
    public function package($packages = [])
    {
        $packages = is_array($packages) ? $packages : [$packages];
        $packages = (0 === count($packages)) ? Package::names() : $packages;
        $this->base = path('system').'console'.DS.'commands'.DS.'test'.DS;

        foreach ($packages as $package) {
            if (is_dir($base = Package::path($package).'tests')) {
                $this->stub($base);
                $this->kickstart();
            }
        }
    }

    /**
     * Jalankan phpunit konfigurasi xml sementara.
     *
     * @return void
     */
    protected function kickstart()
    {
        $phpunit = 'vendor'.DS.'bin'.DS.'phpunit';
        $config = path('base').'phpunit.xml';

        if (! is_file(path('base').$phpunit)) {
            throw new \Exception(
                "Error: PHPUnit 4.8.34 is not present. Please run 'composer install' first."
            );
        }

        $phpunit .= get_cli_option('verbose') ? ' --debug' : '';
        passthru('.'.DS.$phpunit.' --configuration '.escapeshellarg($config), $status);

        Storage::delete($config);
        exit($status);
    }

    /**
     * Salin stub phpunit.xml ke folder root.
     *
     * @param string $directory
     *
     * @return void
     */
    protected function stub($directory)
    {
        $stub = Storage::get(__DIR__.DS.'stub.xml');
        $tokens = ['[bootstrap]' => $this->base.'phpunit.php', '[directory]' => $directory];
        $stub = $this->tokens($stub, $tokens);

        Storage::put(path('base').'phpunit.xml', $stub, LOCK_EX);
    }

    /**
     * Replace string token didalam file stub.
     *
     * @param string $stub
     * @param array  $tokens
     *
     * @return string
     */
    protected function tokens($stub, array $tokens)
    {
        return str_replace(array_keys($tokens), array_values($tokens), $stub);
    }
}
