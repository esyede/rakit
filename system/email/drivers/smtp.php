<?php

namespace System\Email\Drivers;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Request;

class Smtp extends Driver
{
    /**
     * Koneksi SMTP.
     *
     * @var resource
     */
    protected $connection;

    /**
     * Mulai proses transmisi data.
     *
     * @return bool
     */
    protected function transmit()
    {
        try {
            return $this->deliver();
        } catch (\Throwable $e) {
            if ($this->connection) {
                $this->disconnect();
            }

            throw $e;
        } catch (\Exception $e) {
            if ($this->connection) {
                $this->disconnect();
            }

            throw new \Exception('Failed sending email through smtp: ' . $e->getMessage());
        }
    }

    /**
     * Lakukan transmisi email.
     *
     * @return bool
     */
    protected function deliver()
    {
        $message = $this->build(true);

        if (empty($this->config['smtp']['host']) || empty($this->config['smtp']['port'])) {
            throw new \Exception('Must supply a SMTP host and port, none given.');
        }

        $authenticate = (empty($this->connection)
            && !empty($this->config['smtp']['username'])
            && !empty($this->config['smtp']['password']));

        $this->connect();

        if ($authenticate) {
            $this->authenticate();
        }

        $retpath = empty($this->config['return_path'])
            ? $this->config['from']['email']
            : $this->config['return_path'];

        $this->command('MAIL FROM: <' . $retpath . '>', 250);

        $lists = ['to', 'cc', 'bcc'];

        foreach ($lists as $list) {
            foreach ($this->{$list} as $recipient) {
                $this->command('RCPT TO: <' . $recipient['email'] . '>', [250, 251]);
            }
        }

        $this->command('DATA', 354);

        $lines = explode(
            $this->config['newline'],
            $message['header'] . preg_replace('/^\./m', '..$1', $message['body'])
        );

        foreach ($lines as $line) {
            $line = (('.' === substr((string) $line, 0, 1)) ? '.' : '') . $line;
            fputs($this->connection, $line . $this->config['newline']);
        }

        $this->command('.', 250);

        if (!$this->pipelining) {
            $this->disconnect();
        }

        return true;
    }

    /**
     * Buat koneksi ke server SMTP.
     *
     * @return null
     */
    protected function connect()
    {
        if ($this->connection) {
            return;
        }

        if (false === strpos((string) $this->config['smtp']['host'], '://')) {
            $this->config['smtp']['host'] = 'tcp://' . $this->config['smtp']['host'];
        }

        $context = stream_context_create();

        if (
            is_array($this->config['smtp']['options'])
            && !empty($this->config['smtp']['options'])
        ) {
            stream_context_set_option($context, $this->config['smtp']['options']);
        }

        $this->connection = stream_socket_client(
            $this->config['smtp']['host'] . ':' . $this->config['smtp']['port'],
            $errno,
            $errstr,
            $this->config['smtp']['timeout'],
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (empty($this->connection)) {
            throw new \Exception(sprintf('Could not connect to SMTP: (%s) %s.', $errno, $errstr));
        }

        $this->response();

        try {
            $this->command('EHLO ' . Request::server('SERVER_NAME', 'localhost.local'), 250);
        } catch (\Throwable $e) {
            $this->command('HELO ' . Request::server('SERVER_NAME', 'localhost.local'), 250);
        } catch (\Exception $e) {
            $this->command('HELO ' . Request::server('SERVER_NAME', 'localhost.local'), 250);
        }

        if (
            Arr::get($this->config, 'smtp.starttls', false)
            && 0 === strpos((string) $this->config['smtp']['host'], 'tcp://')
        ) {
            try {
                $this->command('STARTTLS', 220);

                $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;

                // Lihat: https://www.php.net/manual/en/function.stream-socket-enable-crypto.php#119122
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                    $crypto |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
                }

                if (!stream_socket_enable_crypto($this->connection, true, $crypto)) {
                    throw new \Exception('STARTTLS failed, Crypto client can not be enabled.');
                }
            } catch (\Throwable $e) {
                throw new \Exception('STARTTLS failed, invalid return code received from server.');
            } catch (\Exception $e) {
                throw new \Exception('STARTTLS failed, invalid return code received from server.');
            }

            try {
                $this->command('EHLO ' . Request::server('SERVER_NAME', 'localhost.local'), 250);
            } catch (\Throwable $e) {
                $this->command('HELO ' . Request::server('SERVER_NAME', 'localhost.local'), 250);
            } catch (\Exception $e) {
                $this->command('HELO ' . Request::server('SERVER_NAME', 'localhost.local'), 250);
            }
        }

        try {
            $this->command('HELP', false);
        } catch (\Throwable $e) {
            throw new \Exception('Unable to send help command: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Unable to send help command: ' . $e->getMessage());
        }
    }

    /**
     * Putuskan koneksi dari server SMTP.
     *
     * @return void
     */
    protected function disconnect()
    {
        $this->command('QUIT', false);

        if (is_resource($this->connection)) {
            fclose($this->connection);
        }

        $this->connection = null;
    }

    /**
     * Jalankan proses otentikasi.
     *
     * @return void
     */
    protected function authenticate()
    {
        $username = base64_encode($this->config['smtp']['username']);
        $password = base64_encode($this->config['smtp']['password']);

        try {
            $this->command('AUTH LOGIN', 334);
            $this->command($username, 334);
            $this->command($password, 235);
        } catch (\Throwable $e) {
            throw new \Exception('AUTH LOGIN failed.');
        } catch (\Exception $e) {
            throw new \Exception('AUTH LOGIN failed.');
        }
    }

    /**
     * Kirim sebaris perintah ke server SMTP.
     *
     * @param string      $command
     * @param string|bool $expecting
     * @param bool        $return_number
     *
     * @return mixed
     */
    protected function command($command, $expecting, $return_number = false)
    {
        if (!is_array($expecting) && false !== $expecting) {
            $expecting = [$expecting];
        }

        stream_set_timeout($this->connection, $this->config['smtp']['timeout']);

        if (!fputs($this->connection, $command . $this->config['newline'])) {
            if (false === $expecting) {
                return false;
            }

            throw new \Exception(sprintf('Failed executing command: %s', $command));
        }

        $info = stream_get_meta_data($this->connection);

        if (isset($info['timed_out']) && $info['timed_out']) {
            throw new \Exception('SMTP connection timed out.');
        }

        $response = $this->response();
        $number = (int) substr(trim((string) $response), 0, 3);

        if (false !== $expecting && !in_array($number, $expecting)) {
            throw new \Exception(sprintf(
                'Got an unexpected response from host on command: [%s] expecting: %s received: %s',
                $command,
                implode(' or ', $expecting),
                $response
            ));
        }

        return $return_number ? $number : $response;
    }

    /**
     * Ambil respon dari server SMTP.
     *
     * @return string
     */
    protected function response()
    {
        $data = '';

        stream_set_timeout($this->connection, $this->config['smtp']['timeout']);

        while ($str = (string) fgets($this->connection, 512)) {
            $info = stream_get_meta_data($this->connection);

            if (isset($info['timed_out']) && $info['timed_out']) {
                throw new \Exception('SMTP connection timed out.');
            }

            $data .= $str;

            if (' ' === substr($str, 3, 1)) {
                break;
            }
        }

        return $data;
    }

    /**
     * Destruktor.
     */
    public function __destruct()
    {
        if ($this->connection) {
            $this->disconnect();
        }
    }
}
