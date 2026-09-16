<?php

namespace System\Log\Drivers;

defined('DS') or exit('No direct access.');

class Discard extends Driver
{
    /**
     * Discard the log record.
     *
     * @param array $record
     *
     * @return bool
     */
    protected function write(array $record)
    {
        return true;
    }
}
