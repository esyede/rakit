<?php

namespace System;

defined('DS') or exit('No direct access.');

class Concurrency
{
    /**
     * The connections a forked child keeps referenced but never uses: they are shared with
     * the parent, and closing them would close the parent's.
     *
     * @var array
     */
    protected static $inherited = [];

    /**
     * Run closures at the same time and return their results, under the same keys.
     *
     *     list($users, $orders) = Concurrency::run([
     *         function () { return User::count(); },
     *         function () { return Order::count(); },
     *     ]);
     *
     * On the CLI with the pcntl extension each closure runs in a forked child process; other
     * environments (php-fpm, no pcntl) run them one after another, with the same results. A
     * closure's result must be serializable; an exception it throws is thrown here, after
     * every closure ended.
     *
     * @param array $tasks closures
     *
     * @return array
     */
    public static function run(array $tasks)
    {
        foreach ($tasks as $task) {
            if (! ($task instanceof \Closure)) {
                throw new \InvalidArgumentException('Concurrency::run() takes closures.');
            }
        }

        return static::forkable() ? static::fork($tasks) : static::sync($tasks);
    }

    /**
     * Check whether closures can run in forked processes.
     *
     * @return bool
     */
    public static function forkable()
    {
        return 'cli' === PHP_SAPI && function_exists('pcntl_fork') && function_exists('pcntl_waitpid');
    }

    /**
     * Run the closures one after another.
     *
     * @param array $tasks
     *
     * @return array
     */
    protected static function sync(array $tasks)
    {
        $results = [];

        foreach ($tasks as $key => $task) {
            $results[$key] = $task();
        }

        return $results;
    }

    /**
     * Run each closure in a forked child, the result coming back through a temporary file.
     *
     * @param array $tasks
     *
     * @return array
     */
    protected static function fork(array $tasks)
    {
        $children = [];

        foreach ($tasks as $key => $task) {
            $file = tempnam(sys_get_temp_dir(), 'rakit-concurrency-');
            $pid = pcntl_fork();

            if (-1 === $pid) {
                throw new \RuntimeException('Unable to fork a child process.');
            }

            if (0 === $pid) {
                static::child($task, $file);
            }

            $children[$key] = [$pid, $file];
        }

        $results = [];
        $error = null;

        foreach ($children as $key => $child) {
            pcntl_waitpid($child[0], $status);
            $outcome = @unserialize((string) @file_get_contents($child[1]));
            @unlink($child[1]);

            if (is_array($outcome) && array_key_exists('result', $outcome)) {
                $results[$key] = $outcome['result'];
            } elseif (is_null($error)) {
                $error = is_array($outcome) && isset($outcome['error'])
                    ? $outcome['error']
                    : 'The child process ended without a result (exit status '.$status.').';
            }
        }

        if (! is_null($error)) {
            throw new \RuntimeException($error);
        }

        return $results;
    }

    /**
     * Run a closure in the forked child, store its outcome, and end the child.
     *
     * @param \Closure $task
     * @param string   $file
     */
    protected static function child(\Closure $task, $file)
    {
        static::$inherited[] = Database::$connections;
        Database::$connections = [];

        try {
            $outcome = ['result' => $task()];
        } catch (\Throwable $e) {
            $outcome = ['error' => get_class($e).': '.$e->getMessage()];
        } catch (\Exception $e) {
            $outcome = ['error' => get_class($e).': '.$e->getMessage()];
        }

        try {
            $data = serialize($outcome);
        } catch (\Exception $e) {
            $data = serialize(['error' => 'The result is not serializable: '.$e->getMessage()]);
        }

        file_put_contents($file, $data);

        if (function_exists('posix_kill') && function_exists('posix_getpid')) {
            posix_kill(posix_getpid(), defined('SIGKILL') ? SIGKILL : 9);
        }

        exit(0);
    }
}
