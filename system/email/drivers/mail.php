<?php

namespace System\Email\Drivers;

defined('DS') or exit('No direct script access.');

class Mail extends Driver
{
    /**
     * Mulai proses transmisi data.
     *
     * @return bool
     */
    protected function transmit()
    {
        try {
            $message = $this->build();
            $retpath = (false !== $this->config['return_path'])
                ? $this->config['return_path']
                : $this->config['from']['email'];

            mail(
                static::format($this->to),
                $this->subject,
                $message['body'],
                $message['header'],
                '-oi -f '.$retpath
            );

            return true;
        } catch (\Throwable $e) {
            throw new \Exception('Failed sending email through mail().');
        } catch (\Exception $e) {
            throw new \Exception('Failed sending email through mail().');
        }
    }
}
