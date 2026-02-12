<?php

namespace System\Console\Commands\Test;

defined('DS') or exit('No direct access.');

use System\Storage;
use System\Package;
use System\Console\Commands\Command;

class Runner extends Command
{
    /**
     * Contains the base directory where tests will be executed.
     * The phpunit.xml file must also be saved in this directory.
     *
     * @var string
     */
    protected $base;

    /**
     * Run all unit tests of the application folder.
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
     * Run all unit tests of the system folder.
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
     * Run all unit tests of a package.
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
     * Run phpunit using the temporary configuration file.
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

        // Forward all phpunit arguments
        $args = $this->arguments();
        $phpunit .= $args ? ' ' . $args : '';

        passthru('.' . DS . $phpunit . ' --configuration ' . escapeshellarg($config), $status);

        if (is_file($config)) {
            Storage::delete($config);
        }

        exit($status);
    }

    /**
     * Copy the phpunit.xml stub to root folder.
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
     * Replace tokens in stub file.
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
     * Get all phpunit arguments.
     *
     * @return string
     */
    protected function arguments()
    {
        $argv = (array) \System\Request::foundation()->server->get('argv');
        $argv = empty($argv) ? [] : $argv;
        $cmdpos = 0;
        $args = [];

        foreach ($argv as $index => $arg) {
            if (strpos($arg, 'test:') !== false) {
                $cmdpos = $index;
                break;
            }
        }

        // Skip the argument after command and package name (if test:package)
        $istart = $cmdpos + 1;

        // If it's a test:package command, skip the package name also
        if (isset($argv[$cmdpos]) && strpos($argv[$cmdpos], 'test:package') !== false) {
            $istart++;
        }

        foreach (array_slice($argv, $istart) as $argument) {
            // Skip the verbos and configuration arguments because they are already handled
            if (in_array($argument, ['-v', '-vv', '-vvv', '--verbose', '-c', '--configuration'])) {
                continue;
            }

            $args[] = escapeshellarg($argument);
        }

        return implode(' ', $args);
    }
}
