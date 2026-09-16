<?php

namespace System\Log\Drivers;

defined('DS') or exit('No direct access.');

class Syslog extends Driver
{
    /**
     * Write the log record into the system logger.
     *
     * @param array $record
     *
     * @return bool
     */
    protected function write(array $record)
    {
        $priorities = [
            'debug' => LOG_DEBUG,
            'info' => LOG_INFO,
            'notice' => LOG_NOTICE,
            'warning' => LOG_WARNING,
            'error' => LOG_ERR,
            'critical' => LOG_CRIT,
            'alert' => LOG_ALERT,
            'emergency' => LOG_EMERG,
        ];

        if (! function_exists('openlog') || ! function_exists('syslog')) {
            throw new \RuntimeException('The openlog() and syslog() functions are not available.');
        }

        $ident = (string) $this->option('ident', $this->name($record));
        $flags = (int) $this->option('flags', LOG_PID);
        $facility = (int) $this->option('facility', LOG_USER);

        // Reopened on every write, other code may have changed the identity in between.
        if (! openlog($ident, $flags, $facility)) {
            throw new \RuntimeException(sprintf('Unable to open syslog for: %s', $ident));
        }

        if (! syslog($priorities[strtolower((string) $record['level'])], $this->format($record, false))) {
            throw new \RuntimeException('Unable to write to syslog.');
        }

        return true;
    }
}
