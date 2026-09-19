<?php

namespace System\Log\Drivers;

defined('DS') or exit('No direct access.');

class Stream extends Driver
{
    /**
     * Contains the opened stream handle.
     *
     * @var resource|null
     */
    protected $handle;

    /**
     * Write the log record into a stream, such as php://stderr.
     *
     * @param array $record
     *
     * @return bool
     */
    protected function write(array $record)
    {
        $stream = (string) $this->option('stream', 'php://stderr');

        if (! is_resource($this->handle)) {
            $this->handle = $this->open($stream);
        }

        if (false === @fwrite($this->handle, $this->format($record).PHP_EOL)) {
            // The other end may have gone away, reopen on the next write.
            $this->handle = null;
            throw new \RuntimeException(sprintf('Unable to write to log stream: %s', $stream));
        }

        return true;
    }

    /**
     * Open the stream for appending.
     *
     * @param string $stream
     *
     * @return resource
     */
    protected function open($stream)
    {
        $guard = '';

        if (false === strpos($stream, '://')) {
            $directory = dirname($stream);

            if (is_file($stream) ? ! is_writable($stream) : ! (is_dir($directory) && is_writable($directory))) {
                throw new \RuntimeException(sprintf('Log stream is not writable: %s', $stream));
            }

            // Read before the file is opened, opening it in append mode creates it.
            $guard = static::guard($stream);
        }

        $handle = @fopen($stream, 'a');

        if (! is_resource($handle)) {
            throw new \RuntimeException(sprintf('Unable to open log stream: %s', $stream));
        }

        if ('' !== $guard) {
            @fwrite($handle, $guard);
        }

        return $handle;
    }
}
