<?php

namespace System\Email\Drivers;

defined('DS') or exit('No direct access.');

class Sendmail extends Driver
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
            // Note: this builds a shell command, so the return path has to be
            // escaped - it ends up straight on the command line otherwise.
            $command = $this->config['sendmail_binary'] . ' -oi -f ' . escapeshellarg($retpath) . ' -t';
            $handle = popen($command, 'w');

            if (!is_resource($handle)) {
                throw new \Exception('Failed sending email through sendmail: unable to start the process');
            }

            fputs($handle, $message['header']);
            fputs($handle, $message['body']);

            if (-1 === pclose($handle)) {
                throw new \Exception('Failed sending email through sendmail: process file pointer fails');
            }

            return true;
        } catch (\Throwable $e) {
            throw new \Exception('Failed sending email through sendmail: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Failed sending email through sendmail: ' . $e->getMessage());
        }
    }
}
