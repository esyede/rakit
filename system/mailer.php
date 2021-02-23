<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Mailer
{
    /**
     * Opsi pengiriman email.
     */
    const TO = 1;
    const CC = 2;
    const BCC = 3;

    /**
     * Tipe otentkasi.
     */
    const SMTP = 11;
    const XOAUTH2 = 12;

    /**
     * Tipe email.
     */
    const PLAIN = 21;
    const HTML = 22;

    /**
     * Berisi resource koneksi SMTP.
     *
     * @var resource
     */
    protected $connection;

    /**
     * Format hello kustom.
     *
     * @var string
     */
    protected $hello = 'EHLO';

    /**
     * Berisi list alamat 'reply-to'.
     *
     * @var array
     */
    protected $replyto = [];

    /**
     * Berisi list alamat penerima.
     *
     * @var array
     */
    protected $recipients = [];

    /**
     * Berisi list lampiran file.
     *
     * @var array
     */
    protected $attachments = [];

    /**
     * Berisi list header.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Berisi log komunikasi SMTP.
     *
     * @var array
     */
    protected $logs = [];

    /**
     * Berisi respnse code dari server.
     *
     * @var int
     */
    protected $code;

    /**
     * Berisi respnse message dari server.
     *
     * @var int
     */
    protected $message;

    /**
     * Berisi status dibaca dari server.
     *
     * @var bool
     */
    protected $read;

    /**
     * Berisi status pengiriman dari server.
     *
     * @var bool
     */
    protected $delivery;

    /**
     * Berisi detail akun email.
     *
     * @var array
     */
    private $credentials;

    /**
     * Buat Mailer object baru.
     *
     * @param string $host
     * @param int    $port
     * @param string $username
     * @param string $password
     * @param int    $method
     */
    public function __construct($host, $port, $username, $password, $method = self::SMTP)
    {
        $method = (int) $method;

        if (self::SMTP !== $method && self::XOAUTH2 !== $method) {
            throw new \Exception('Only Mailer::SMTP and Mailer::XOAUTH2 authentication type are supported.');
        }

        $tls = false;
        $this->credentials = compact('host', 'port', 'username', 'password', 'method', 'tls');

        if ('tls://' === substr($host, 0, 6)) {
            $this->credentials['tls'] = true;
            $this->credentials['host'] = substr($host, 6);
        }
    }

    /**
     * Putuskan koneksi.
     */
    public function __destruct()
    {
        if (is_resource($this->connection)) {
            fclose($this->connection);
        }

        $this->connection = null;
    }

    /**
     * Ambil log komunikasi SMTP.
     *
     * @return array
     */
    public function logs()
    {
        return $this->logs;
    }

    /**
     * Aktifkan laporan dibaca.
     */
    public function read()
    {
        $this->read = true;
    }

    /**
     * Aktifkn status pengiriman.
     */
    public function delivery()
    {
        $this->delivery = true;
    }

    /**
     * Set string hello kustom.
     *
     * @param string $hello
     */
    public function hello($hello)
    {
        $this->hello = ('HELO' === strtoupper($hello)) ? 'HELO' : 'EHLO';
    }

    /**
     * Tambahkan penerima (copy-carbon).
     *
     * @param string $email
     * @param string $name
     *
     * @return bool
     */
    public function cc($email, $name = null)
    {
        return $this->to($email, $name, self::CC);
    }

    /**
     * Tambahkan penerima (back copy-carbon).
     *
     * @param string $email
     * @param string $name
     *
     * @return bool
     */
    public function bcc($email, $name = null)
    {
        return $this->to($email, $name, self::BCC);
    }

    /**
     * Tambahkan penerima.
     *
     * @param string $email
     * @param string $name
     * @param int    $type
     *
     * @return bool
     */
    public function to($email, $name = null, $type = self::TO)
    {
        if (self::TO !== $type && self::CC !== $type && self::BCC !== $type) {
            throw new \Exception('Only Mailer::TO, Mailer::CC or Mailer::BCC recipient type are supported.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->log('Invalid email address: '.$email);
            return false;
        }

        $email = mb_strtolower($email);
        $this->recipients[] = compact('email', 'name', 'type');

        return true;
    }

    /**
     * Set alamat reply-to.
     *
     * @param string $email
     * @param string $name
     *
     * @return bool
     */
    public function replyto($email, $name = null)
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->log('Invalid email address: '.$email);
            return false;
        }

        $this->replyto = compact('email', 'name');

        return true;
    }

    /**
     * Tambahkan lampiran.
     *
     * @param string $file
     * @param string $name
     *
     * @return bool
     */
    public function attach($file, $name = null)
    {
        if (! is_file($file)) {
            $this->log('Invalid attachment file: '.$file);
            return false;
        }

        $this->attachments[] = [
            'name' => $name ? $name : basename($file),
            'type' => File::mime($file),
            'path' => $file,
        ];

        return true;
    }

    /**
     * Tambahkan header kustom.
     *
     * @param string $key
     * @param string $value
     */
    public function header($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * Kirimkn email.
     *
     * @param string $from
     * @param string $name
     * @param string $subject
     * @param string $body
     * @param int    $mode
     * @param string $message
     *
     * @return bool
     */
    public function send($from, $name, $subject, $body, $mode = self::PLAIN, $message = null)
    {
        $from = mb_strtolower($from);
        $mode = (int) $mode;

        if (self::PLAIN !== $mode && self::HTML !== $mode) {
            throw new \Exception('Only Mailer::PLAIN and Mailer::HTML mailing mode are supported.');
        }

        if (! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $this->log('Invalid email address: '.$from);
            return false;
        }

        if (empty($this->recipients)) {
            $this->log('There is no recipient added.');
            return false;
        }

        if (! $this->connect()) {
            return false;
        }

        $this->execute('MAIL FROM: <'.$from.'>');

        if (250 !== $this->code) {
            $this->terminate('MAIL command failed.');
            return false;
        }

        foreach ($this->recipients as $recipient) {
            $this->execute(
                'RCPT TO: <'.$recipient['email'].'>'.
                ($this->delivery ? ' NOTIFY=SUCCESS,FAILURE,DELAY' : '')
            );

            if (250 !== $this->code) {
                $this->terminate('RECPT TO command failed.');
                return false;
            }
        }

        $this->execute('DATA');

        if (354 !== $this->code) {
            $this->terminate('DATA command failed.');
            return false;
        }

        // Buang newline yang tidak diperlukan
        $subject = str_replace(["\r", "\n"], '', trim($subject));
        $body = str_replace("\r", '', trim($body));

        $contents = [];
        $boundary1 = md5(Str::random(16));
        $boundary2 = md5(Str::random(16));

        $headers = [
            'Message-ID: <'.time().'.'.md5(microtime()).'@'.substr($from, strrpos($from, '@') + 1).'>',
            'From: '.($name ? '"'.$this->encode($name).'" ' : '').'<'.$from.'>',
        ];

        if (! empty($this->headers)) {
            foreach ($this->headers as $key => $value) {
                $headers[] = $key.': '.$value;
            }
        }

        if (! empty($this->replyto)) {
            $headers[] = 'Reply-To: '.(($this->replyto['name'])
                ? '"'.$this->encode($this->replyto['name']).'" ' : '').
                '<'.$this->replyto['email'].'>';
        }

        $to_list = [];
        $cc_list = [];

        foreach ($this->recipients as $recipient) {
            if (self::TO === (int) $recipient['type']) {
                $to_list[] = ($recipient['name'] ? '"'.$this->encode($recipient['name']).'" ' : '').
                    '<'.$recipient['email'].'>';
            }

            if (self::CC === (int) $recipient['type']) {
                $cc_list[] = ($recipient['name']
                    ? '"'.$this->encode($recipient['name']).'" ' : '').
                    '<'.$recipient['email'].'>';
            }
        }

        $headers[] = 'To: '.implode(', ', $to_list);
        $headers[] = 'CC: '.implode(', ', $cc_list);
        $headers[] = 'Subject: '.$this->encode($subject);
        $headers[] = 'Date: '.date('r');
        $headers[] = 'Importance: Normal';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Return-Path: <'.$from.'>';

        if ($this->read) {
            $headers[] = 'Disposition-Notification-To: '.
                ($name ? '"'.$this->encode($name).'" ' : '').'<'.$from.'>';

            $headers[] = 'Return-Receipt-To: '.
                ($name ? '"'.$this->encode($name).'" ' : '').'<'.$from.'>';
        }

        if (self::PLAIN === (int) $mode) {
            if (empty($this->attachments)) {
                $headers[] = 'Content-Type: text/plain; charset="utf-8"';
                $headers[] = 'Content-Transfer-Encoding: 7bit';
            } else {
                $headers[] = 'Content-Type: multipart/mixed;';
                $headers[] = '  boundary="'.$boundary1.'"';
                $headers[] = '--'.$boundary1;
                $headers[] = 'Content-Type: text/plain; charset="utf-8"';
                $headers[] = 'Content-Transfer-Encoding: 7bit';
            }

            $contents[] = $body;
        } else {
            if (! empty($this->attachments)) {
                $headers[] = 'Content-Type: multipart/mixed;';
                $headers[] = '  boundary="'.$boundary1.'"';
            } else {
                $headers[] = 'Content-Type: multipart/alternative;';
                $headers[] = '  boundary="'.$boundary2.'"';
            }

            if (! empty($this->attachments)) {
                $contents[] = '--'.$boundary1.CRLF.
                    'Content-Type: multipart/alternative; boundary="'.$boundary2.'"'.CRLF;
            }

            $contents[] = '--'.$boundary2;
            $contents[] = 'Content-Type: text/plain; charset="UTF-8"';
            $contents[] = 'Content-Transfer-Encoding: quoted-printable'.CRLF;

            $message = quoted_printable_encode($message ? $message : strip_tags($body));
            $contents[] = preg_replace('/\n\./', "\n..", $message);

            $contents[] = '--'.$boundary2;
            $contents[] = 'Content-Type: text/html; charset="UTF-8"';
            $contents[] = 'Content-Transfer-Encoding: quoted-printable'.CRLF;
            $contents[] = preg_replace('/\n\./', "\n..", quoted_printable_encode($body));
            $contents[] = '--'.$boundary2.'--';
        }

        if (! empty($this->attachments)) {
            foreach ($this->attachments as $attachment) {
                $contents[] = CRLF.'--'.$boundary1;
                $contents[] = 'Content-Type: '.$attachment['type'].'; name="'.$attachment['name'].'"';
                $contents[] = 'Content-Transfer-Encoding: base64';
                $contents[] = 'Content-Disposition: attachment; filename="'.$attachment['name'].'"'.CRLF;

                $handle = fopen($attachment['path'], 'rb');

                if (! $handle) {
                    $this->terminate('Error opening file "'.$attachment['path'].'".');
                    return false;
                }

                $contents[] = chunk_split(base64_encode(fread($handle, filesize($attachment['path']))));
                fclose($handle);
            }

            $contents[] = '--'.$boundary1.'--';
        }

        $this->execute(implode(CRLF, $headers).CRLF.CRLF.implode(CRLF, $contents).CRLF.'.');

        if (250 !== $this->code) {
            $this->terminate('DATA command failed.');
            return false;
        }

        $this->disconnect();

        return true;
    }

    /**
     * Tulis log.
     *
     * @param string $string
     */
    protected function log($string)
    {
        $this->logs[] = sprintf('[%s]  %s', date('Y-m-d H:i:s'), $string);
    }

    /**
     * Buat koneksi ke server.
     *
     * @return bool
     */
    private function connect()
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $this->log('Connecting to '.$this->credentials['host'].':'.$this->credentials['port'].'.');

        $this->connection = @stream_socket_client(
            $this->credentials['host'].':'.$this->credentials['port'],
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (! $this->connection) {
            $this->log('Connection failed: '.$errstr);
            return false;
        }

        $this->log('Connection established.');

        // Ambil banner server
        $this->response();

        if (220 !== $this->code) {
            $this->log('Remote server not responding.');
            return false;
        }

        // Kirim hello
        $this->execute($this->hello.' 127.0.0.1');

        if (250 !== $this->code) {
            $this->terminate('Server not responding to greeting.');
            return false;
        }

        if ($this->credentials['tls']) {
            $this->execute('STARTTLS');

            if (220 !== $this->code) {
                $this->terminate('STARTTLS command failed.');
                return false;
            }

            $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;

            // Lihat: https://www.php.net/manual/en/function.stream-socket-enable-crypto.php#119122
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                $crypto |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
            }

            // Lihat: https://www.php.net/manual/en/function.stream-socket-enable-crypto.php#75442
            stream_set_blocking($this->connection, true);

            if (! stream_socket_enable_crypto($this->connection, true, $crypto)) {
                $this->terminate('Unable to start TLS connection.');
                return false;
            }

            stream_set_blocking($this->connection, false);

            // Kirim hello lagi
            $this->execute($this->hello.' 127.0.0.1');

            if (250 !== $this->code) {
                $this->terminate('Server not responding to greeting.');
                return false;
            }
        }

        switch ($this->credentials['method']) {
            case self::SMTP:
                $this->execute('AUTH LOGIN');

                if (334 !== $this->code) {
                    $this->terminate('AUTH LOGIN not accepted.');
                    return false;
                }

                $this->execute(base64_encode($this->credentials['username']));

                if (334 !== $this->code) {
                    $this->terminate('Username not accepted.');
                    return false;
                }

                $this->execute(base64_encode($this->credentials['password']));
                break;

            case self::XOAUTH2:
                $this->execute('AUTH XOAUTH2 '.base64_encode(
                    'user='.$this->credentials['username']."\1".
                    'auth=Bearer '.$this->credentials['password']."\1\1"
                ));
                break;

            default:
                $this->terminate('Only smtp (11) and xoauth2 (12) authentication are supported.');
                return false;
        }

        if (235 !== $this->code) {
            $this->terminate('Authentication failed.');
            return false;
        }

        return true;
    }

    /**
     * Encode string ke UTF-8.
     *
     * @param string $text
     *
     * @return string
     */
    private function encode($text)
    {
        if (strlen($text) !== mb_strlen($text, 'utf-8')) {
            return '=?UTF-8?B?'.base64_encode($text).'?=';
        }

        return $text;
    }

    /**
     * Kirim perintah ke server.
     *
     * @param string $command
     */
    private function execute($command)
    {
        @fwrite($this->connection, $command.CRLF);
        $this->log('# '.$command);
        $this->response();
    }

    /**
     * Ambil respon dari server.
     */
    private function response()
    {
        while ($data = @fgets($this->connection, 515)) {
            $this->log(trim($data));

            if (' ' === substr($data, 3, 1)) {
                break;
            }
        }

        $this->code = (int) substr($data, 0, 3);
        $this->message = substr($data, 4);
    }

    /**
     * Putuskan koneksi.
     */
    private function disconnect()
    {
        $this->execute('QUIT');

        if (is_resource($this->connection)) {
            fclose($this->connection);
        }

        $this->connection = null;
        $this->replyto = [];
        $this->recipients = [];
        $this->attachments = [];
        $this->headers = [];
    }

    /**
     * Putuskan koneksi (dengan tambahan pesan error).
     *
     * @param string $message
     *
     * @return false
     */
    private function terminate($message)
    {
        $this->disconnect();
        $this->log($message);

        return false;
    }
}
