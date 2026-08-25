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
            $sender = $this->envelope_sender();
            $parameters = (null === $sender) ? '-oi' : '-oi -f '.$sender;

            return (bool) mail(
                static::format($this->to),
                $this->subject,
                $message['body'],
                $message['header'],
                $parameters
            );
        } catch (\Throwable $e) {
            throw new \Exception('Failed sending email through mail: '.$e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Failed sending email through mail: '.$e->getMessage());
        }
    }
}
