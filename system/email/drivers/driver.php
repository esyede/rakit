<?php

namespace System\Email\Drivers;

defined('DS') or exit('No direct script access.');

use System\Arr;
use System\Str;
use System\Email;
use System\Storage;

abstract class Driver
{
    /**
     * Konfigurasi.
     *
     * @var array
     */
    protected $config = [];

    /**
     * List penerima email.
     *
     * @var array
     */
    protected $to = [];

    /**
     * List penerima email (copy carbon).
     *
     * @var array
     */
    protected $cc = [];

    /**
     * List penerima email (back copy carbon).
     *
     * @var array
     */
    protected $bcc = [];

    /**
     * List penerima balasan email.
     *
     * @var array
     */
    protected $replyto = [];

    /**
     * List lampiran.
     *
     * @var array
     */
    protected $attachments = ['inline' => [], 'attachment' => []];

    /**
     * Body email.
     *
     * @var string
     */
    protected $body = '';

    /**
     * Alternate body.
     *
     * @var string
     */
    protected $alt_body = '';

    /**
     * Subyek email.
     *
     * @var string
     */
    protected $subject = '';

    /**
     * List alamat email yang tidak lolos validasi.
     *
     * @var array
     */
    protected $invalid_addresses = [];

    /**
     * Pembatas pesan.
     *
     * @var array
     */
    protected $boundaries = [];

    /**
     * List header.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * List header kustom.
     *
     * @var array
     */
    protected $extras = [];

    /**
     * Aktifkan pipelining?
     *
     * @var bool
     */
    protected $pipelining = false;

    /**
     * Tipe email.
     *
     * @var string
     */
    protected $type = 'plain';

    /**
     * Konstruktor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Aktif/nonaktifkan pipelining driver.
     *
     * @param bool $activate
     *
     * @return $this
     */
    public function pipelining($activate = true)
    {
        $this->pipelining = (bool) $activate;

        return $this;
    }

    /**
     * Set body email.
     *
     * @param string $body
     *
     * @return $this
     */
    public function body($body)
    {
        $this->body = (string) $body;

        return $this;
    }

    /**
     * Set alternative body email.
     *
     * @param string $body
     *
     * @return $this
     */
    public function alt_body($body)
    {
        $this->alt_body = (string) $body;

        return $this;
    }

    /**
     * Set prioritas email.
     *
     * @param string $priority
     *
     * @return $this
     */
    public function priority($priority)
    {
        $priorities = [Email::LOWEST, Email::LOW, Email::NORMAL, Email::HIGH, Email::HIGHEST];

        if (! in_array($priority, $priorities)) {
            throw new \Exception(sprintf('Invalid email priority: %s', $priority));
        }

        $this->config['priority'] = $priority;

        return $this;
    }

    /**
     * Set body email (HTML).
     * Method ini juga membuat alternate body secara otomatis.
     *
     * @param string $html
     * @param bool   $alternatify
     * @param bool   $attachify
     *
     * @return $this
     */
    public function html_body($html, $alternatify = null, $attachify = null)
    {
        $this->config['as_html'] = true;

        $alternatify = is_bool($alternatify) ? $alternatify : $this->config['alternatify'];
        $attachify = is_bool($attachify) ? $attachify : $this->config['attachify'];
        $strip = isset_or($this->config['strip_comments'], true);
        $html = $strip ? preg_replace('/<!--(.*)-->/', '', (string) $html) : $html;

        if ($attachify) {
            preg_match_all('/(src|background)="(.*)"/Ui', $html, $images);

            if (isset($images[2]) && ! empty($images[2])) {
                foreach ($images[2] as $i => $url) {
                    if (! preg_match('/(^http\:\/\/|^https\:\/\/|^\/\/|^cid\:|^data\:|^#)/Ui', $url)) {
                        $cid = 'cid:'.md5(basename($url));

                        if (! isset($this->attachments['inline'][$cid])) {
                            $this->attach($url, true, $cid);
                        }

                        $html = preg_replace(
                            '/'.$images[1][$i].'="'.preg_quote($url, '/').
                            '"/Ui', $images[1][$i].'="'.$cid.'"',
                            $html
                        );
                    } elseif ($scheme = Arr::get($this->config, 'protocol_replacement', false)
                    && 0 === strpos($url, '//')) {
                        $html = preg_replace(
                            '/'.$images[1][$i].'="'.preg_quote($url, '/').
                            '"/Ui', $images[1][$i].'="'.$scheme.substr($url, 2).'"',
                            $html
                        );
                    }
                }
            }
        }

        $this->body = $html;

        if ($alternatify) {
            $this->alt_body = static::alternatify(
                $html,
                $this->config['wordwrap'],
                $this->config['newline']
            );
        }

        return $this;
    }

    /**
     * Set subyek email.
     *
     * @param string $subject
     *
     * @return $this
     */
    public function subject($subject)
    {
        $this->subject = $this->config['encode_headers']
            ? $this->encode((string) $subject)
            : (string) $subject;

        return $this;
    }

    /**
     * Set alamat pengirim email.
     *
     * @param string      $email
     * @param bool|string $name
     *
     * @return $this
     */
    public function from($email, $name = false)
    {
        $this->config['from']['email'] = (string) $email;
        $this->config['from']['name'] = is_string($name) ? $name : false;

        if ($this->config['encode_headers'] && $this->config['from']['name']) {
            $this->config['from']['name'] = $this->encode((string) $this->config['from']['name']);
        }

        return $this;
    }

    /**
     * Tambahkan list penerima email.
     *
     * @param string|array $email
     * @param string|bool  $name
     *
     * @return $this
     */
    public function to($email, $name = false)
    {
        static::append('to', $email, $name);

        return $this;
    }

    /**
     * Tambahkan list penerima email (copy carbon).
     *
     * @param string|array $email
     * @param string|bool  $name
     *
     * @return $this
     */
    public function cc($email, $name = false)
    {
        static::append('cc', $email, $name);

        return $this;
    }

    /**
     * Tambahkan list penerima email (back copy carbon).
     *
     * @param string|array $email
     * @param string|bool  $name
     *
     * @return $this
     */
    public function bcc($email, $name = false)
    {
        static::append('bcc', $email, $name);

        return $this;
    }

    /**
     * Tambahkan list penerima balasan email.
     *
     * @param string|array $email
     * @param string|bool  $name
     *
     * @return $this
     */
    public function replyto($email, $name = false)
    {
        static::append('replyto', $email, $name);

        return $this;
    }

    /**
     * Set alamat return-path.
     *
     * @param string $email
     *
     * @return $this
     */
    public function return_path($email)
    {
        $this->config['return_path'] = (string) $email;

        return $this;
    }

    /**
     * Tambahkan ke list penerima.
     *
     * @param string       $list
     * @param string|array $email
     * @param string|bool  $name
     */
    protected function append($list, $email, $name = false)
    {
        if (! is_array($email)) {
            $email = is_string($name) ? [$email => $name] : [$email];
        }

        foreach ($email as $address => $name) {
            if (is_numeric($address)) {
                $address = $name;
                $name = false;
            }

            $name = ($this->config['encode_headers'] && $name) ? $this->encode($name) : $name;
            $this->{$list}[$address] = ['name' => $name, 'email' => $address];
        }
    }

    /**
     * Reset properti email.
     *
     * @return $this
     */
    public function reset()
    {
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->replyto = [];
        $this->attachments = ['inline' => [], 'attachment' => []];

        return $this;
    }

    /**
     * Set header kustom.
     *
     * @param string|array $headers
     * @param string       $value
     *
     * @return $this
     */
    public function header($headers, $value = null)
    {
        if (is_array($headers)) {
            foreach ($headers as $key => $val) {
                if (! empty($val)) {
                    $this->extras[$key] = $val;
                }
            }
        } else {
            if (! empty($value)) {
                $this->extras[$headers] = $value;
            }
        }

        return $this;
    }

    /**
     * Lampirkan file ke email.
     *
     * @param string $file
     * @param bool   $inline
     * @param string $cid
     * @param string $mime
     * @param string $name
     *
     * @return $this
     */
    public function attach($file, $inline = false, $cid = null, $mime = null, $name = null)
    {
        $file = (array) $file;

        if (! is_file($file[0])) {
            throw new \Exception(sprintf('Email attachment not found: %s', $file[0]));
        }

        $file[1] = isset_or($file[1], ($name ? $name : basename($file[0])));

        if (false === ($contents = file_get_contents($file[0])) || empty($contents)) {
            throw new \Exception(sprintf(
                'Could not read attachment or attachment is empty: %s', $file[0]
            ));
        }

        $disp = $inline ? 'inline' : 'attachment';
        $cid = empty($cid) ? 'cid:'.md5($file[1]) : trim($cid);
        $cid = (0 === strpos($cid, 'cid:')) ? $cid : 'cid:'.$cid;

        $mime = $mime ? $mime : static::mime($file[0]);
        $contents = chunk_split(base64_encode($contents), 76, $this->config['newline']);

        $this->attachments[$disp][$cid] = compact('file', 'contents', 'mime', 'disp', 'cid');

        return $this;
    }

    /**
     * Lampirkan file menggunakan inputan string.
     *
     * @param string $contents
     * @param string $filename
     * @param string $cid
     * @param bool   $inline
     * @param string $mime
     *
     * @return $this
     */
    public function string_attach($contents, $filename, $cid = null, $inline = false, $mime = null)
    {
        $disp = $inline ? 'inline' : 'attachment';
        $cid = empty($cid) ? 'cid:'.md5($filename) : trim($cid);
        $cid = (0 === strpos($cid, 'cid:')) ? $cid : 'cid:'.$cid;

        $mime = $mime ? $mime : static::mime($filename);
        $file = [$filename, basename($filename)];
        $contents = static::encode_string($contents, 'base64', $this->config['newline']);

        $this->attachments[$disp][$cid] = compact('file', 'contents', 'mime', 'disp', 'cid');

        return $this;
    }

    /**
     * Tebak mime-type file lampiran.
     *
     * @param string $file
     *
     * @return $this
     */
    protected static function mime($file)
    {
        $mime = Storage::mime($file);

        return $mime ? $mime : 'application/octet-stream';
    }

    /**
     * Validasi seuruh alamat email.
     *
     * @return bool|array
     */
    protected function validation()
    {
        $lists = ['to', 'cc', 'bcc'];
        $failed = [];

        foreach ($lists as $list) {
            foreach ($this->{$list} as $value) {
                if (! filter_var($value['email'], FILTER_VALIDATE_EMAIL)) {
                    $failed[$list][] = $value;
                }
            }
        }

        return (0 === count($failed)) ? true : $failed;
    }

    /**
     * Mulai proses pengiriman.
     *
     * @param bool $validate
     *
     * @return bool
     */
    public function send($validate = null)
    {
        if (empty($this->to) && empty($this->cc) && empty($this->bcc)) {
            throw new \Exception('Cannot send email without recipients.');
        }

        if (false === ($from = $this->config['from']['email']) || empty($from)) {
            throw new \Exception('Cannot send without from address.');
        }

        $validate = is_bool($validate) ? $validate : $this->config['validate'];

        if ($validate && true !== ($failed = $this->validation())) {
            $this->invalid_addresses = $failed;
            $error = '';

            foreach ($failed as $list => $contents) {
                $error .= $list.': '.e(static::format($contents)).'.'.PHP_EOL;
            }

            throw new \Exception(sprintf(
                'One or more email addresses did not pass validation: %s', $error
            ));
        }

        $this->headers = [];
        $boundary = md5(Str::random(16));
        $this->boundaries = ['B1_'.$boundary, 'B2_'.$boundary, 'B3_'.$boundary];
        $this->set_header('Date', date('r'));

        $path = (false === $this->config['return_path'])
            ? $this->config['from']['email']
            : $this->config['return_path'];

        $this->set_header('Return-Path', $path);

        if (! ($this instanceof Mail)) {
            if (! empty($this->to)) {
                $this->set_header('To', static::format($this->to));
            }

            $this->set_header('Subject', $this->subject);
        }

        $this->set_header('From', static::format([$this->config['from']]));
        $lists = ['cc' => 'Cc', 'bcc' => 'Bcc', 'replyto' => 'Reply-To'];

        foreach ($lists as $list => $header) {
            if (count($this->{$list}) > 0) {
                $this->set_header($header, static::format($this->{$list}));
            }
        }

        $message_id = '<'.md5(Str::random(16)).strstr($this->config['from']['email'], '@').'>';
        $this->set_header('Message-ID', $message_id);
        $this->set_header('MIME-Version', '1.0');
        $this->set_header('X-Priority', $this->config['priority']);
        $this->set_header('X-Mailer', $this->config['mailer']);

        $type = $this->config['as_html'] ? 'html' : 'plain' ;
        $type .= ($this->config['as_html'] && ! empty(trim($this->alt_body))) ? '_alt' : '';
        $type .= ($this->config['as_html'] && count($this->attachments['inline'])) ? '_inline' : '';
        $type .= (count($this->attachments['attachment'])) ? '_attach' : '';
        $this->type = $type;

        $newline = $this->config['newline'];
        $encoding = $this->config['encoding'];

        if ('plain' !== $this->type && 'html' !== $this->type) {
            $bond = $newline."\tboundary=\"".$this->boundaries[0].'"';
            $relate = $this->config['force_mixed'] ? 'multipart/mixed; ' : 'multipart/related; ';

            switch ($this->type) {
                case 'plain': $type = 'text/plain'; break;
                case 'plain_attach':
                case 'html_attach': $type = $relate.$bond; break;
                case 'html': $type = 'text/html'; break;
                case 'html_alt_attach':
                case 'html_alt_inline_attach': $type = 'multipart/mixed; '.$bond; break;
                case 'html_alt_inline':
                case 'html_alt':
                case 'html_inline': $type = 'multipart/alternative; '.$bond; break;
                default: throw new \Exception(sprintf('Invalid content-type: %s', $this->type));
            }

            $this->set_header('Content-Type', $type);
        } else {
            $this->set_header('Content-Transfer-Encoding', $encoding);
            $this->set_header('Content-Type', 'text/'.$this->type.'; charset=utf-8');
        }

        $this->body = static::encode_string($this->body, $encoding, $newline);
        $this->alt_body = static::encode_string($this->alt_body, $encoding, $newline);

        $wrapping = $this->config['wordwrap'];
        $qp_mode = ($encoding === 'quoted-printable');
        $as_html = (false !== stripos($this->type, 'html'));

        if ($wrapping && ! $qp_mode) {
            $this->body = static::wrap($this->body, $wrapping, $newline, $as_html);
            $this->alt_body = static::wrap($this->alt_body, $wrapping, $newline, false);
        }

        $this->transmit();

        return true;
    }

    /**
     * Ambil list alamat email yang tidak lolos validasi.
     *
     * @return array
     */
    public function get_invalid_addresses()
    {
        return $this->invalid_addresses;
    }

    /**
     * Set header email.
     *
     * @param string
     * @param string
     */
    protected function set_header($header, $value)
    {
        if (! empty($header)) {
            $this->headers[$header] = $value;
        }
    }

    /**
     * Ambil header email.
     *
     * @param string $header
     * @param bool   $formatted
     *
     * @return string|array
     */
    protected function get_header($header = null, $formatted = true)
    {
        if (is_null($header)) {
            return $this->headers;
        }

        if (array_key_exists($header, $this->headers)) {
            $prefix = $formatted ? $header.': ' : '';
            $suffix = $formatted ? $this->config['newline'] : '';

            return $prefix.$this->headers[$header].$suffix;
        }

        return '';
    }

    /**
     * Encode mime header.
     *
     * @param string $header
     *
     * @return string
     */
    protected function encode($header)
    {
        $encoding = ('quoted-printable' === $this->config['encoding']) ? 'Q' : 'B' ;

        return mb_encode_mimeheader($header, 'utf-8', $encoding, $this->config['newline']);
    }

    /**
     * Ambil header lampiran.
     */
    protected function get_attachment_headers($type, $boundary)
    {
        $eol = $this->config['newline'];
        $out = '';

        foreach ($this->attachments[$type] as $data) {
            $out .= '--'.$boundary.$eol;
            $out .= 'Content-Type: '.$data['mime'].'; name="'.$data['file'][1].'"'.$eol;
            $out .= 'Content-Transfer-Encoding: base64'.$eol;
            $out .= ('inline' === $type) ? 'Content-ID: <'.substr($data['cid'], 4).'>'.$eol : '';
            $out .= 'Content-Disposition: '.$type.'; filename="'.$data['file'][1].'"'.$eol.$eol;
            $out .= $data['contents'].$eol.$eol;
        }

        return $out;
    }

    /**
     * Susun header dan body email.
     *
     * @param bool $without_bcc
     *
     * @return array
     */
    protected function build($without_bcc = false)
    {
        $eol = $this->config['newline'];
        $encoding = $this->config['encoding'];
        $parts = [
            'Date', 'Return-Path', 'From', 'To',
            'Cc', 'Bcc', 'Reply-To', 'Subject', 'Message-ID',
            'X-Priority', 'X-Mailer', 'MIME-Version', 'Content-Type',
        ];

        if ($without_bcc) {
            array_splice($parts, 5, 1);
        }

        $header = '';

        foreach ($parts as $part) {
            $header .= $this->get_header($part);
        }

        foreach ($this->extras as $key => $value) {
            $header .= $key.': '.$value.$eol;
        }

        $header .= $eol;
        $body = '';

        if ('plain' === $this->type || 'html' === $this->type) {
            $body = $this->body;
        } else {
            switch ($this->type) {
                case 'html_alt':
                    $body .= '--'.$this->boundaries[0].$eol;
                    $body .= 'Content-Type: text/plain; charset=utf-8'.$eol;
                    $body .= 'Content-Transfer-Encoding: '.$encoding.$eol.$eol;
                    $body .= $this->alt_body.$eol.$eol;
                    $body .= '--'.$this->boundaries[0].$eol;
                    $body .= 'Content-Type: text/html; charset=utf-8'.$eol;
                    $body .= 'Content-Transfer-Encoding: '.$encoding.$eol.$eol;
                    $body .= $this->body.$eol.$eol;
                    $body .= '--'.$this->boundaries[0].'--';
                    break;

                case 'plain_attach':
                case 'html_attach':
                case 'html_inline':
                    $body .= '--'.$this->boundaries[0].$eol;
                    $ctype = (false !== stripos($this->type, 'html')) ? 'html' : 'plain';
                    $body .= 'Content-Type: text/'.$ctype.'; charset=utf-8'.$eol;
                    $body .= 'Content-Transfer-Encoding: '.$encoding.$eol.$eol;
                    $body .= $this->body.$eol.$eol;
                    $ctype = (false !== stripos($this->type, 'attach')) ? 'attachment' : 'inline';
                    $body .= $this->get_attachment_headers($ctype, $this->boundaries[0]);
                    $body .= '--'.$this->boundaries[0].'--';
                    break;

                case 'html_alt_inline':
                    $body .= '--'.$this->boundaries[0].$eol;
                    $body .= 'Content-Type: text/plain'.'; charset=utf-8'.$eol;
                    $body .= 'Content-Transfer-Encoding: '.$encoding.$eol.$eol;
                    $body .= $this->alt_body.$eol.$eol;
                    $body .= '--'.$this->boundaries[0].$eol;
                    $body .= 'Content-Type: multipart/related;'.$eol.
                        "\tboundary=\"".$this->boundaries[1].'"'.$eol.$eol;
                    $body .= '--'.$this->boundaries[1].$eol;
                    $body .= 'Content-Type: text/html; charset=utf-8'.$eol;
                    $body .= 'Content-Transfer-Encoding: '.$encoding.$eol.$eol;
                    $body .= $this->body.$eol.$eol;
                    $body .= $this->get_attachment_headers('inline', $this->boundaries[1]);
                    $body .= '--'.$this->boundaries[1].'--'.$eol.$eol;
                    $body .= '--'.$this->boundaries[0].'--';
                    break;

                case 'html_alt_attach':
                case 'html_inline_attach':
                    $body .= '--'.$this->boundaries[0].$eol;
                    $body .= 'Content-Type: multipart/alternative;'.$eol.
                        "\t boundary=\"".$this->boundaries[1].'"'.$eol.$eol;

                    if (false !== stripos($this->type, 'alt')) {
                        $body .= '--'.$this->boundaries[1].$eol;
                        $body .= 'Content-Type: text/plain; charset=utf-8'.$eol;
                        $body .= 'Content-Transfer-Encoding: '.$encoding.$eol.$eol;
                        $body .= $this->alt_body.$eol.$eol;
                    }

                    $body .= '--'.$this->boundaries[1].$eol;
                    $body .= 'Content-Type: text/html; charset=utf-8'.$eol;
                    $body .= 'Content-Transfer-Encoding: '.$encoding.$eol.$eol;
                    $body .= $this->body.$eol.$eol;

                    if (false !== stripos($this->type, 'inline')) {
                        $body .= $this->get_attachment_headers('inline', $this->boundaries[1]);
                        $body .= $this->alt_body.$eol.$eol;
                    }

                    $body .= '--'.$this->boundaries[1].'--'.$eol.$eol;
                    $body .= $this->get_attachment_headers('attachment', $this->boundaries[0]);
                    $body .= '--'.$this->boundaries[0].'--';
                    break;

                case 'html_alt_inline_attach':
                    $body .= '--'.$this->boundaries[0].$eol;
                    $body .= 'Content-Type: multipart/alternative;'.$eol.
                        "\t boundary=\"".$this->boundaries[1].'"'.$eol.$eol;
                    $body .= '--'.$this->boundaries[1].$eol;
                    $body .= 'Content-Type: text/plain; charset=utf-8'.$eol;
                    $body .= 'Content-Transfer-Encoding: '.$encoding.$eol.$eol;
                    $body .= $this->alt_body.$eol.$eol;
                    $body .= '--'.$this->boundaries[1].$eol;
                    $body .= 'Content-Type: multipart/related;'.$eol.
                        "\t boundary=\"".$this->boundaries[2].'"'.$eol.$eol;
                    $body .= '--'.$this->boundaries[2].$eol;
                    $body .= 'Content-Type: text/html; charset=utf-8'.$eol;
                    $body .= 'Content-Transfer-Encoding: '.$encoding.$eol.$eol;
                    $body .= $this->body.$eol.$eol;
                    $body .= $this->get_attachment_headers('inline', $this->boundaries[2]);
                    $body .= $this->alt_body.$eol.$eol;
                    $body .= '--'.$this->boundaries[2].'--'.$eol.$eol;
                    $body .= '--'.$this->boundaries[1].'--'.$eol.$eol;
                    $body .= $this->get_attachment_headers('attachment', $this->boundaries[0]);
                    $body .= '--'.$this->boundaries[0].'--';
                    break;
            }
        }

        return compact('header', 'body');
    }

    /**
     * Wrap teks.
     *
     * @param string $message
     * @param int    $length
     * @param string $newline
     * @param bool   $as_html
     *
     * @return string
     */
    protected static function wrap($message, $length, $newline, $as_html = true)
    {
        $length = ($length > 76) ? 76 : $length;
        $message = $as_html ? preg_replace('/[\r\n\t ]+/m', ' ', $message) : $message;
        $message = wordwrap($message, $length, $newline, false);

        return $message;
    }

    /**
     * Standarisasi newline.
     *
     * @param string $string
     * @param string $newline
     *
     * @return string
     */
    protected static function standardize($string, $newline = null)
    {
        $newline = $newline ? $newline : isset_or($this->config['email.newline'], "\n");
        $replace = ["\r\n" => "\n", "\n\r" => "\n", "\r" => "\n", "\n" => $newline];

        foreach ($replace as $from => $to) {
            $string = str_replace($from, $to, $string);
        }

        return $string;
    }

    /**
     * Encode string menurut encoding yang diberikan.
     *
     * @param string $string
     * @param string $encoding
     * @param string $newline
     *
     * @return string
     */
    protected static function encode_string($string, $encoding, $newline = null)
    {
        $newline = $newline ? $newline : isset_or($this->config['email.newline'], "\n");

        switch ($encoding) {
            case '7bit':
            case '8bit':             return static::standardize(rtrim($string, $newline), $newline);
            case 'quoted-printable': return quoted_printable_encode($string);
            case 'base64':           return chunk_split(base64_encode($string), 76, $newline);
            default:                 throw new \Exception(sprintf('Unupported encoding method: %s.', $encoding));
        }
    }

    /**
     * Mereturn string alamat email yang telah diformat.
     *
     * @param array $addresses
     *
     * @return string
     */
    protected static function format($addresses)
    {
        $result = [];

        foreach ($addresses as $address) {
            if (isset($address['name']) && $address['name']) {
                $address['email'] = '"'.$address['name'].'" <'.$address['email'].'>';
            }

            $result[] = $address['email'];
        }

        return implode(', ', $result);
    }

    /**
     * Buat alternate body.
     *
     * @param string $html
     * @param int    $wordwrap
     * @param string $newline
     *
     * @return string
     */
    protected static function alternatify($html, $wordwrap, $newline)
    {
        $html = preg_replace('/[ |  ]{2,}/m', ' ', $html);
        $html = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s', '', $html)));
        $lines = explode($newline, $html);

        $first_newline = true;
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (! empty($line) || $first_newline) {
                $first_newline = false;
                $result[] = $line;
            } else {
                $first_newline = true;
            }
        }

        $html = implode($newline, $result);

        return $wordwrap ? wordwrap($html, $wordwrap, $newline, true) : $html;
    }

    /**
     * Mulai proses transmisi data.
     *
     * @return bool
     */
    abstract protected function transmit();
}
