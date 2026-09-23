<?php

namespace System;

defined('DS') or exit('No direct access.');

use System\Log\Formatter;

class Log
{
    /**
     * Contains the name of the log channel.
     *
     * @var string|null
     */
    protected static $channel;

    /**
     * Contains all resolved log drivers, keyed by channel name.
     *
     * @var array
     */
    public static $drivers = [];

    /**
     * Contains all third-party log driver registrars.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Set the name of the log channel.
     *
     * A name from "log.channels" selects that channel; any other name becomes the
     * log name on the default channel. Pass null to reset.
     *
     * @param string|null $name
     *
     * @return void
     */
    public static function channel($name = null)
    {
        static::$channel = (is_string($name) && '' !== trim($name)) ? trim($name) : null;
    }

    /**
     * Get a channel's log driver, or the default channel's.
     *
     * @param string|null $channel
     *
     * @return \System\Log\Drivers\Driver
     */
    public static function driver($channel = null)
    {
        $channel = is_null($channel) ? static::target() : $channel;

        if (! is_string($channel) || '' === $channel) {
            throw new \Exception('Log channel must be a non-empty string');
        }

        if (! isset(static::$drivers[$channel])) {
            static::$drivers[$channel] = static::factory($channel);
        }

        return static::$drivers[$channel];
    }

    /**
     * Register a log driver. The resolver takes ($config, $name) and returns a Driver.
     *
     * @param string   $driver
     * @param \Closure $resolver
     *
     * @return void
     */
    public static function extend($driver, \Closure $resolver)
    {
        static::$registrar[$driver] = $resolver;
        static::$drivers = [];
    }

    /**
     * Get all log levels, keyed by name, ordered by severity.
     *
     * @return array
     */
    public static function levels()
    {
        return [
            'debug' => 100,
            'info' => 200,
            'notice' => 250,
            'warning' => 300,
            'error' => 400,
            'critical' => 500,
            'alert' => 550,
            'emergency' => 600,
        ];
    }

    /**
     * Write a log with an arbitrary level.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    public static function log($level, $message, array $context = [])
    {
        $levels = static::levels();

        if (! is_string($level) || ! isset($levels[strtolower($level)])) {
            throw new \InvalidArgumentException(
                sprintf('Unsupported log level: %s', is_string($level) ? $level : gettype($level))
            );
        }

        static::write(strtolower($level), $message, $context);
    }

    /**
     * Write an emergency log.
     *
     * @param string $message
     * @param array  $context
     */
    public static function emergency($message, array $context = [])
    {
        static::write('emergency', $message, $context);
    }

    /**
     * Write an alert log.
     *
     * @param string $message
     * @param array  $context
     */
    public static function alert($message, array $context = [])
    {
        static::write('alert', $message, $context);
    }

    /**
     * Write a critical log.
     *
     * @param string $message
     * @param array  $context
     */
    public static function critical($message, array $context = [])
    {
        static::write('critical', $message, $context);
    }

    /**
     * Write an error log.
     *
     * @param string $message
     * @param array  $context
     */
    public static function error($message, array $context = [])
    {
        static::write('error', $message, $context);
    }

    /**
     * Write a warning log.
     *
     * @param string $message
     * @param array  $context
     */
    public static function warning($message, array $context = [])
    {
        static::write('warning', $message, $context);
    }

    /**
     * Write a notice log.
     *
     * @param string $message
     * @param array  $context
     */
    public static function notice($message, array $context = [])
    {
        static::write('notice', $message, $context);
    }

    /**
     * Write an info log.
     *
     * @param string $message
     * @param array  $context
     */
    public static function info($message, array $context = [])
    {
        static::write('info', $message, $context);
    }

    /**
     * Write a debug log.
     *
     * @param string $message
     * @param array  $context
     */
    public static function debug($message, array $context = [])
    {
        static::write('debug', $message, $context);
    }

    /**
     * Write the log through the active channel.
     *
     * @param string $type
     * @param string $message
     * @param array  $context
     */
    protected static function write($type, $message, array $context = [])
    {
        if (! is_string($message)) {
            throw new \Exception(sprintf('The error message should be a string. %s given.', gettype($message)));
        }

        if (Hook::exists('rakit.log')) {
            Hook::fire('rakit.log', [$type, $message, $context]);
        }

        $record = [
            'level' => $type,
            'message' => $message,
            'context' => $context,
            'channel' => is_string(static::$channel) ? static::$channel : static::name(),
            'env' => static::environment(),
            'datetime' => Carbon::now(),
        ];

        $channel = null;

        try {
            $channel = static::target();

            if (false === static::driver($channel)->handle($record)) {
                throw new \RuntimeException('The driver was unable to write the log entry.');
            }
        } catch (\Throwable $e) {
            static::fallback($record, $channel, $e);
        } catch (\Exception $e) {
            static::fallback($record, $channel, $e);
        }

        // Track log for debugger
        if (class_exists('\System\Foundation\Oops\Debugger') && class_exists('\System\Foundation\Oops\Collectors')) {
            if (! \System\Foundation\Oops\Debugger::$productionMode) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
                \System\Foundation\Oops\Collectors::addLog(
                    $type,
                    $message,
                    $context,
                    isset($trace[1]['file']) ? $trace[1]['file'] : null,
                    isset($trace[1]['line']) ? $trace[1]['line'] : null
                );

                // Also surface a context exception on the Exceptions panel.
                if (isset($context['exception'])
                    && ($context['exception'] instanceof \Throwable || $context['exception'] instanceof \Exception)) {
                    \System\Foundation\Oops\Collectors::addException($context['exception']);
                }
            }
        }
    }

    /**
     * Make a new log driver instance for the channel.
     *
     * @param string $channel
     *
     * @return \System\Log\Drivers\Driver
     */
    protected static function factory($channel)
    {
        $config = static::config($channel);
        $driver = isset($config['driver']) ? $config['driver'] : null;

        if (! is_string($driver) || '' === $driver) {
            throw new \Exception(sprintf('Log channel has no driver: %s', $channel));
        }

        if (isset(static::$registrar[$driver])) {
            $instance = call_user_func(static::$registrar[$driver], $config, $channel);
        } elseif ('custom' === $driver) {
            $instance = static::custom($config, $channel);
        } else {
            switch ($driver) {
                case 'daily':    return new Log\Drivers\Daily($config, $channel);
                case 'single':   return new Log\Drivers\Single($config, $channel);
                case 'stream':   return new Log\Drivers\Stream($config, $channel);
                case 'syslog':   return new Log\Drivers\Syslog($config, $channel);
                case 'errorlog': return new Log\Drivers\Errorlog($config, $channel);
                case 'stack':    return new Log\Drivers\Stack($config, $channel);
                case 'null':     return new Log\Drivers\Discard($config, $channel);
                default:         throw new \Exception(sprintf('Unsupported log driver: %s', $driver));
            }
        }

        if (! ($instance instanceof Log\Drivers\Driver)) {
            throw new \Exception(sprintf('Log driver must be an instance of System\Log\Drivers\Driver: %s', $driver));
        }

        return $instance;
    }

    /**
     * Make a driver instance for the "custom" driver, using its "via" option.
     *
     * @param array  $config
     * @param string $channel
     *
     * @return mixed
     */
    protected static function custom(array $config, $channel)
    {
        $via = isset($config['via']) ? $config['via'] : null;

        if ($via instanceof \Closure) {
            return $via($config, $channel);
        }

        if (! is_string($via) || ! class_exists($via)) {
            throw new \Exception(sprintf('Log channel needs a valid "via" class or closure: %s', $channel));
        }

        return new $via($config, $channel);
    }

    /**
     * Get the configuration of a channel.
     *
     * @param string $channel
     *
     * @return array
     */
    protected static function config($channel)
    {
        $channels = Config::get('log.channels');

        // Applications without a log config keep writing daily files, as they always did.
        if (! is_array($channels) && 'daily' === $channel) {
            return ['driver' => 'daily'];
        }

        if (! is_array($channels) || ! isset($channels[$channel]) || ! is_array($channels[$channel])) {
            throw new \Exception(sprintf('Log channel is not defined: %s', $channel));
        }

        return $channels[$channel];
    }

    /**
     * Get the name of the channel the next entry is written to.
     *
     * @return string
     */
    protected static function target()
    {
        if (is_string(static::$channel)) {
            $channels = Config::get('log.channels');

            if (is_array($channels) && isset($channels[static::$channel])) {
                return static::$channel;
            }
        }

        $default = Config::get('log.default');
        return (is_string($default) && '' !== $default) ? $default : 'daily';
    }

    /**
     * Get the default log name.
     *
     * @return string
     */
    protected static function name()
    {
        $name = Config::get('application.name');
        return (is_string($name) && '' !== $name) ? $name : 'rakit';
    }

    /**
     * Get the environment label written into log entries.
     *
     * @return string
     */
    protected static function environment()
    {
        return (
            class_exists('\System\Foundation\Oops\Debugger')
            && isset(\System\Foundation\Oops\Debugger::$productionMode)
        )
            ? (\System\Foundation\Oops\Debugger::$productionMode ? 'production' : 'local')
            : 'unknown';
    }

    /**
     * Write the entry and the channel failure to the emergency log, or PHP's error log.
     *
     * @param array                 $record
     * @param string|null           $channel
     * @param \Throwable|\Exception $e
     *
     * @return void
     */
    protected static function fallback(array $record, $channel, $e)
    {
        try {
            $reason = sprintf('Unable to write log to the "%s" channel: %s', (string) $channel, $e->getMessage());
            $lines = [Formatter::line(['level' => 'error', 'message' => $reason, 'context' => []] + $record)];

            try {
                $lines[] = Formatter::line($record);
            } catch (\Throwable $ex) {
                $lines[] = Formatter::line(['context' => []] + $record);
            } catch (\Exception $ex) {
                $lines[] = Formatter::line(['context' => []] + $record);
            }

            $file = path('storage').'logs'.DS.'rakit.log.php';
            $directory = dirname($file);
            $writable = is_file($file) ? is_writable($file) : (is_dir($directory) && is_writable($directory));
            $payload = Log\Drivers\Driver::guard($file).implode(PHP_EOL, $lines).PHP_EOL;

            if (! $writable || false === @file_put_contents($file, $payload, FILE_APPEND | LOCK_EX)) {
                foreach ($lines as $line) {
                    @error_log($line);
                }
            }
        } catch (\Throwable $ex) {
            // Nowhere left to report to
        } catch (\Exception $ex) {
            // Nowhere left to report to
        }
    }
}
