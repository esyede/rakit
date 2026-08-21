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

            // Note: this builds a shell command, so the return path has to be
            // escaped - it ends up straight on the command line otherwise.
            $command = $this->config['sendmail_binary'] . ' -oi'
                . ((null === $sender) ? '' : ' -f ' . escapeshellarg($sender)) . ' -t';
            $handle = popen($command, 'w');

            if (!is_resource($handle)) {
                throw new \Exception('Failed sending email through sendmail: unable to start the process');
            }

            fputs($handle, $message['header']);
            fputs($handle, $message['body']);

            // Note: pclose() answers the exit status, and only -1 was treated as
            // a failure - so a sendmail that refused the message and quit with,
            // say, 67 (EX_NOUSER) was still reported as a successful send.
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
