<?php

namespace System\Log\Drivers;

defined('DS') or exit('No direct access.');

use System\Log;

class Stack extends Driver
{
    /**
     * Contains the stack channels currently being written.
     *
     * @var array
     */
    protected static $writing = [];

    /**
     * Write the log record into every channel of the stack.
     *
     * @param array $record
     *
     * @return bool
     */
    protected function write(array $record)
    {
        $errors = [];

        if (is_string($this->channel)) {
            static::$writing[$this->channel] = true;
        }

        foreach ((array) $this->option('channels', []) as $channel) {
            if (isset(static::$writing[$channel])) {
                $errors[] = sprintf('[%s] Circular reference in log stack.', $channel);
                continue;
            }

            // One failing channel must not keep the others from receiving the record.
            try {
                if (false === Log::driver($channel)->handle($record)) {
                    $errors[] = sprintf('[%s] Unable to write the log entry.', $channel);
                }
            } catch (\Throwable $e) {
                $errors[] = sprintf('[%s] %s', $channel, $e->getMessage());
            } catch (\Exception $e) {
                $errors[] = sprintf('[%s] %s', $channel, $e->getMessage());
            }
        }

        if (is_string($this->channel)) {
            unset(static::$writing[$this->channel]);
        }

        if (count($errors) > 0 && ! $this->option('ignore_exceptions', false)) {
            throw new \RuntimeException(implode(' ', $errors));
        }

        return true;
    }
}
