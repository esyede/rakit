<?php

namespace System\Email\Drivers;

defined('DS') or exit('No direct access.');

class Sendmail extends Driver
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

            $handle = popen($this->config['sendmail_binary'] . ' -oi -f ' . $retpath . ' -t', 'w');

            fputs($handle, $message['header']);
            fputs($handle, $message['body']);

            if (-1 === pclose($handle)) {
                throw new \Exception(
                    'Failed sending email through sendmail: process file pointer fails'
                );
            }

            return true;
        } catch (\Throwable $e) {
            throw new \Exception('Failed sending email through sendmail: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Failed sending email through sendmail: ' . $e->getMessage());
        }
    }
}
