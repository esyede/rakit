<?php

namespace System\Console\Commands\Test;

defined('DS') or exit('No direct access.');

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
    public function run(array $packages = [])
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
        $this->base = path('base') . 'tests' . DS;
        $this->stub($this->base . 'cases');
        $this->start();
    }

    /**
     * Jalankan seluruh unit test milik sebuah paket.
     *
     * @param array $packages
     *
     * @return void
     */
    public function package(array $packages = [])
    {
        $packages = is_array($packages) ? $packages : [$packages];
        $packages = (0 === count($packages)) ? Package::names() : $packages;
        $this->base = path('system') . 'console' . DS . 'commands' . DS . 'test' . DS;

        foreach ($packages as $package) {
            if (is_dir($base = Package::path($package) . 'tests')) {
                $this->stub($base);
                $this->start();
            }
        }
    }

    /**
     * Jalankan phpunit konfigurasi xml sementara.
     *
     * @return void
     */
    protected function start()
    {
        $phpunit = 'vendor' . DS . 'bin' . DS . 'phpunit';
        $config = path('base') . 'phpunit.xml';

        if (!is_file(path('base') . $phpunit)) {
            throw new \Exception(
                "Error: test dependencies is not present. Please run 'composer install' first."
            );
        }

        $verbose = has_cli_flag('v')
            || has_cli_flag('vv')
            || has_cli_flag('vvv')
            || has_cli_flag('verbose');

        $phpunit .= $verbose ? ' --debug' : '';

        // Forward semua parameter PHPUnit dari CLI
        $args = $this->arguments();
        $phpunit .= $args ? ' ' . $args : '';

        passthru('.' . DS . $phpunit . ' --configuration ' . escapeshellarg($config), $status);

        if (is_file($config)) {
            Storage::delete($config);
        }

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
        $stub = Storage::get(__DIR__ . DS . 'stub.xml');
        $stub = $this->tokens($stub, ['[boot]' => $this->base . 'phpunit.php', '[dir]' => $directory]);

        file_put_contents(path('base') . 'phpunit.xml', $stub, LOCK_EX);
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

    /**
     * Ambil semua parameter PHPUnit dari argument CLI.
     *
     * @return string
     */
    protected function arguments()
    {
        $argv = (array) \System\Request::foundation()->server->get('argv');
        $argv = empty($argv) ? [] : $argv;
        $args = [];

        // Deteksi posisi command test (test:core dan test:package package_name)
        $cmdpos = 0;

        foreach ($argv as $index => $arg) {
            if (strpos($arg, 'test:') !== false) {
                $cmdpos = $index;
                break;
            }
        }

        // Skip argument setelah command dan nama package (jika test:package)
        $istart = $cmdpos + 1;

        // Jika test:package, skip juga nama package
        if (isset($argv[$cmdpos]) && strpos($argv[$cmdpos], 'test:package') !== false) {
            $istart++;
        }

        foreach (array_slice($argv, $istart) as $argument) {
            // Skip parameter verbose dan configuration karena sudah dihandle
            if (in_array($argument, ['-v', '-vv', '-vvv', '--verbose', '-c', '--configuration'])) {
                continue;
            }

            $args[] = escapeshellarg($argument);
        }

        return implode(' ', $args);
    }
}
