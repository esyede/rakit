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
            $sender = $this->envelope_sender();

            $command = $this->config['sendmail_binary'] . ' -oi'
                . ((null === $sender) ? '' : ' -f ' . escapeshellarg($sender)) . ' -t';
            $handle = popen($command, 'w');

            if (!is_resource($handle)) {
                throw new \Exception('Failed sending email through sendmail: unable to start the process');
            }

            fputs($handle, $message['header']);
            fputs($handle, $message['body']);

            $status = pclose($handle);

            if (0 !== $status) {
                throw new \Exception(sprintf(
                    'Failed sending email through sendmail: the process exited with status %d',
                    $status
                ));
            }

            return true;
        } catch (\Throwable $e) {
            throw new \Exception('Failed sending email through sendmail: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Failed sending email through sendmail: ' . $e->getMessage());
        }
    }
}
