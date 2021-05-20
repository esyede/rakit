<?php

namespace System\Console;

defined('DS') or exit('No direct script access.');

use System\Str;
use System\Package;
use System\Container;

class Console
{
    /**
     * Panggil sebuhah command konsol.
     *
     * <code>
     *
     *      // Panggil command migrate
     *      Console::run(['migrate']);
     *
     *      // Panggil command migrate dengan argumen tambahan
     *      Console::run(['migrate:rollback', 'nama-paket'])
     *
     * </code>
     *
     * @param array $arguments
     *
     * @return void
     */
    public static function run($arguments = [])
    {
        if (! isset($arguments[0])) {
            $arguments[0] = 'help:run';
        }

        list($package, $command, $method) = static::parse($arguments[0]);

        if (Package::exists($package)) {
            Package::boot($package);
        }

        $command = static::resolve($package, $command);

        if (is_null($command)) {
            throw new \Exception('Sorry, I cannot find that command.');
        }

        if (is_callable([$command, $method])) {
            $command->{$method}(array_slice($arguments, 1));
        } else {
            throw new \Exception('Sorry, I cannot find that method!');
        }
    }

    /**
     * Ekstrak nama paket, command, dan method.
     *
     * @param string $command
     *
     * @return array
     */
    protected static function parse($command)
    {
        list($package, $command) = Package::parse($command);

        if (Str::contains($command, ':')) {
            list($command, $method) = explode(':', $command);
        } else {
            $method = 'run';
        }

        return [$package, $command, $method];
    }

    /**
     * Resolve instance dari nama command yang diberikan.
     *
     * <code>
     *
     *      // Resolve instance dari sebuah command
     *      $command = Console::resolve('application', 'migrate');
     *
     *      // Resolve instance dari sebuah command milik sebuah paket
     *      $command = Console::resolve('nama_paket', 'foo');
     *
     * </code>
     *
     * @param string $package
     * @param string $command
     *
     * @return object
     */
    public static function resolve($package, $command)
    {
        $identifier = Package::identifier($package, $command);

        if (Container::registered('command: '.$identifier)) {
            return Container::resolve('command: '.$identifier);
        }

        if (is_file($path = Package::path($package).'commands'.DS.$command.'.php')) {
            require_once $path;

            $command = static::format($package, $command);

            return new $command();
        }
    }

    /**
     * Ambil opsi-opsi command.
     *
     * @param array $argv
     *
     * @return array
     */
    public static function options($argv)
    {
        $options = [];
        $arguments = [];

        for ($i = 0, $count = count($argv); $i < $count; $i++) {
            $argument = $argv[$i];

            if (Str::starts_with($argument, '--')) {
                list($key, $value) = [substr($argument, 2), true];

                if (($equals = strpos($argument, '=')) !== false) {
                    $key = substr($argument, 2, $equals - 2);
                    $value = substr($argument, $equals + 1);
                }

                $options[$key] = $value;
            } else {
                $arguments[] = $argument;
            }
        }

        return [$arguments, $options];
    }

    /**
     * Ubah paket dan command menjadi nama kelas.
     *
     * @param string $package
     * @param string $command
     *
     * @return string
     */
    protected static function format($package, $command)
    {
        $prefix = Package::class_prefix($package);

        return '\\'.$prefix.Str::classify($command).'_Command';
    }
}
