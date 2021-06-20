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
        $return_path = ($this->config['return_path'] !== false)
            ? $this->config['return_path']
            : $this->config['from']['email'];

        $handle = @popen($this->config['sendmail_binary'].' -oi -f '.$return_path.' -t', 'w');

        if (! is_resource($handle)) {
            throw new \Exception(sprintf(
                'Could not open a sendmail connection at: %s', $this->config['sendmail_binary']
            ));
        }

        fputs($handle, $message['header']);
        fputs($handle, $message['body']);

        if (pclose($handle) === -1) {
            throw new \Exception('Failed sending email through sendmail.');
        }

        return true;
    }
}
