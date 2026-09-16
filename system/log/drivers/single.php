<?php

namespace System\Log\Drivers;

defined('DS') or exit('No direct access.');

class Single extends Driver
{
    /**
     * Write the log record into a file.
     *
     * @param array $record
     *
     * @return bool
     */
    protected function write(array $record)
    {
        $file = $this->path($record);
        $directory = dirname($file);
        $exists = is_file($file);

        // Checked upfront since a failing write emits a warning, which the
        // debugger turns into an error page when "scream" is enabled.
        if ($exists ? ! is_writable($file) : ! (is_dir($directory) && is_writable($directory))) {
            throw new \RuntimeException(sprintf('Log file is not writable: %s', $file));
        }

        if (false === @file_put_contents($file, $this->format($record).PHP_EOL, FILE_APPEND | LOCK_EX)) {
            throw new \RuntimeException(sprintf('Unable to write to log file: %s', $file));
        }

        if (! $exists) {
            $this->created($file, $record);
        }

        return true;
    }

    /**
     * Get the path of the log file.
     *
     * @param array $record
     *
     * @return string
     */
    protected function path(array $record)
    {
        $path = (string) $this->option('path', '');
        return ('' === $path) ? path('storage').'logs'.DS.$this->name($record).'.log.php' : $path;
    }

    /**
     * Run after the log file has been created.
     *
     * @param string $file
     * @param array  $record
     *
     * @return void
     */
    protected function created($file, array $record)
    {
        $permission = $this->option('permission');

        if (is_int($permission)) {
            @chmod($file, $permission);
        }
    }
}
