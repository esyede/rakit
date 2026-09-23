<?php

namespace System\Log\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Log;
use System\Log\Formatter;

abstract class Driver
{
    /** @var string */
    const GUARD = "<?php defined('DS') or exit('No direct access.');?>";

    /**
     * Contains the channel configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Contains the name of the channel this driver was configured for.
     *
     * @var string|null
     */
    protected $channel;

    /**
     * Contains the minimum level rank handled by this driver.
     *
     * @var int
     */
    protected $minimum;

    /**
     * Make a new log driver instance.
     *
     * @param array       $config
     * @param string|null $channel
     */
    public function __construct(array $config = [], $channel = null)
    {
        $levels = Log::levels();
        $level = strtolower((string) (isset($config['level']) ? $config['level'] : 'debug'));
        $format = isset($config['format']) ? $config['format'] : 'line';

        if (! isset($levels[$level])) {
            throw new \InvalidArgumentException(sprintf('Unsupported log level: %s', $level));
        }

        if ('line' !== $format && 'json' !== $format) {
            throw new \InvalidArgumentException(sprintf('Unsupported log format: %s', $format));
        }

        $this->config = $config;
        $this->channel = $channel;
        $this->minimum = $levels[$level];
    }

    /**
     * Handle the log record.
     *
     * The record holds 'level', 'message', 'context', 'channel' (from Log::channel(),
     * or the application name), 'env' and 'datetime' (a \System\Carbon).
     *
     * @param array $record
     *
     * @return bool
     */
    public function handle(array $record)
    {
        return $this->handles($record['level']) ? (false !== $this->write($record)) : true;
    }

    /**
     * Determine whether the driver handles the given level.
     *
     * @param string $level
     *
     * @return bool
     */
    public function handles($level)
    {
        $levels = Log::levels();
        $level = strtolower((string) $level);

        return isset($levels[$level]) && $levels[$level] >= $this->minimum;
    }

    /**
     * Write the log record. Throw or return false when it cannot be written.
     *
     * @param array $record
     *
     * @return bool
     */
    abstract protected function write(array $record);

    /**
     * Format the log record according to the channel's "format" option.
     *
     * @param array $record
     * @param bool  $timestamp
     *
     * @return string
     */
    protected function format(array $record, $timestamp = true)
    {
        return ('json' === $this->option('format', 'line'))
            ? Formatter::json($record)
            : Formatter::line($record, $timestamp);
    }

    /**
     * Get the log name used for file names and syslog identity.
     *
     * @param array $record
     *
     * @return string
     */
    protected function name(array $record)
    {
        $name = Str::slug((string) $this->option('name', $record['channel']));
        return ('' === $name) ? 'rakit' : $name;
    }

    /**
     * Get the guard written in front of a log file's first entry. Only a file PHP would
     * parse needs one, and only at creation: appended later it would sit behind what it
     * has to protect.
     *
     * @param string $file
     *
     * @return string
     */
    public static function guard($file)
    {
        return ('.php' === strtolower(substr((string) $file, -4)) && ! is_file($file))
            ? static::GUARD.PHP_EOL
            : '';
    }

    /**
     * Get a channel configuration option.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function option($key, $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
}
