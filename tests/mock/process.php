<?php

/*
|--------------------------------------------------------------------------
| Mock Server Process
|--------------------------------------------------------------------------
|
| Runs tests/mock/server.php on a PHP built-in server so the test suite never
| has to reach the network. The server starts on first use and is terminated
| when the PHP process ends, so every test class shares a single instance.
|
| Compatible with PHP 5.4 through 8.5.
|
*/

class MockServer
{
    /**
     * Base URL of the running server, NULL when it could not be started.
     *
     * @var string|null
     */
    private static $base;

    /**
     * @var resource|null
     */
    private static $process;

    /**
     * @var bool
     */
    private static $started = false;

    /**
     * Get the base URL of the mock endpoint, starting the server if needed.
     *
     * @return string|null
     */
    public static function url()
    {
        if (self::$started) {
            return self::$base;
        }

        self::$started = true;

        $port = self::freePort();
        $base = 'http://127.0.0.1:' . $port . '/mock';
        $command = escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:' . $port . ' '
            . escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . 'server.php');

        $pipes = [];
        $options = null;

        if (DIRECTORY_SEPARATOR === '\\') {
            // NUL is Windows' /dev/null. Pipes are not an option: nothing here
            // reads them, and `php -S` logs a line per request to stderr, so
            // the buffer would fill and the server would block mid-run.
            $descriptors = [
                0 => ['file', 'NUL', 'r'],
                1 => ['file', 'NUL', 'w'],
                2 => ['file', 'NUL', 'w'],
            ];

            // Without this, proc_open wraps the command in `cmd.exe /c`, so the
            // pid we hold belongs to cmd; terminating it would leave the server
            // running, and Windows has no posix_kill to fall back on.
            $options = ['bypass_shell' => true];
        } else {
            $descriptors = [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w'],
            ];

            // Same problem, different shell: proc_open runs the command through
            // `sh -c`, so proc_get_status() would report the shell's pid. `exec`
            // makes the shell replace itself with PHP, so the pid is the
            // server's.
            $command = 'exec ' . $command;
        }

        $process = @proc_open($command, $descriptors, $pipes, null, null, $options);
        self::$process = is_resource($process) ? $process : null;

        if (null === self::$process) {
            return null;
        }

        register_shutdown_function(['MockServer', 'stop']);

        // The server needs a moment before it accepts connections.
        for ($i = 0; $i < 50; $i++) {
            if (self::reachable($base)) {
                return self::$base = $base;
            }

            usleep(100000);
        }

        self::stop();

        return null;
    }

    /**
     * Terminate the server.
     *
     * @return void
     */
    public static function stop()
    {
        if (!is_resource(self::$process)) {
            return;
        }

        @proc_terminate(self::$process);

        $status = proc_get_status(self::$process);

        // proc_terminate does not wait, and on some platforms it does not land
        // at all; follow up with a signal before giving up the handle so the
        // server never outlives the test run.
        if (!empty($status['running']) && function_exists('posix_kill')) {
            @posix_kill($status['pid'], defined('SIGKILL') ? SIGKILL : 9);
        }

        @proc_close(self::$process);

        self::$process = null;
        self::$base = null;
    }

    /**
     * Check whether the given URL answers with HTTP 200.
     *
     * @param string $url
     *
     * @return bool
     */
    public static function reachable($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        @curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (PHP_VERSION_ID < 80000) {
            /** @disregard */
            curl_close($ch);
        }

        return 200 === (int) $code;
    }

    /**
     * Ask the OS for a free TCP port.
     *
     * @return int
     */
    private static function freePort()
    {
        $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);

        if (!$socket) {
            return 8910;
        }

        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        $port = (int) substr($name, strrpos($name, ':') + 1);

        return $port > 0 ? $port : 8910;
    }
}
