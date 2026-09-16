<?php

namespace System\Log\Drivers;

defined('DS') or exit('No direct access.');

class Errorlog extends Driver
{
    /**
     * Write the log record using PHP's error_log() function.
     *
     * @param array $record
     *
     * @return bool
     */
    protected function write(array $record)
    {
        // 0: the "error_log" ini destination, 4: the SAPI logger (e.g. the web server log).
        $type = (int) $this->option('type', 0);

        if (0 !== $type && 4 !== $type) {
            throw new \InvalidArgumentException(sprintf('Unsupported error_log() message type: %s', $type));
        }

        if (! error_log($this->format($record, false), $type)) {
            throw new \RuntimeException('Unable to write to the PHP error log.');
        }

        return true;
    }
}
