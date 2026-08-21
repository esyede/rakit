<?php

namespace System\Email\Drivers;

defined('DS') or exit('No direct access.');

class Mail extends Driver
{
    /**
     * Starts the email transmission.
     *
     * @return bool
     */
    protected function transmit()
    {
        try {
            $message = $this->build();
            $retpath = (false !== $this->config['return_path']) ? $this->config['return_path'] : $this->config['from']['email'];
            // Note: the result matters. Returning TRUE unconditionally reported a
            // successful send even when the local MTA refused the message.
            return (bool) mail(
                static::format($this->to),
                $this->subject,
                $message['body'],
                $message['header'],
                '-oi -f ' . $retpath
            );
        } catch (\Throwable $e) {
            throw new \Exception('Failed sending email through mail: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Failed sending email through mail: ' . $e->getMessage());
        }
    }
}
