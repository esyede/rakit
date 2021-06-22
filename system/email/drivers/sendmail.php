<?php

namespace System\Email\Drivers;

defined('DS') or exit('No direct script access.');

class Sendmail extends Driver
{
    /**
     * Mulai proses transmisi data.
     *
     * @return bool
     */
    protected function transmit()
    {
        $message = $this->build();
        $retpath = (false !== $this->config['return_path'])
            ? $this->config['return_path']
            : $this->config['from']['email'];

        $handle = @popen($this->config['sendmail_binary'].' -oi -f '.$retpath.' -t', 'w');

        if (! is_resource($handle)) {
            throw new \Exception(sprintf(
                'Could not open a sendmail connection at: %s', $this->config['sendmail_binary']
            ));
        }

        fputs($handle, $message['header']);
        fputs($handle, $message['body']);

        if (-1 === pclose($handle)) {
            throw new \Exception('Failed sending email through sendmail.');
        }

        return true;
    }
}
