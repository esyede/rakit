<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Messages
{
    /**
     * Berisi seluruh message yang terdaftar.
     *
     * @var array
     */
    public $messages;

    /**
     * Format default untuk output.
     *
     * @var string
     */
    public $format = ':message';

    /**
     * Buat instance Message baru.
     *
     * @param array $messages
     */
    public function __construct($messages = [])
    {
        $this->messages = (array) $messages;
    }

    /**
     * Tambahkan sebuat message ke collector.
     *
     * <code>
     *
     *      // Tambahkam message untuk atribut 'email'
     *      $messages->add('email', 'Email yang Anda masukkan tidak sah.');
     *
     * </code>
     *
     * @param string $key
     * @param string $message
     */
    public function add($key, $message)
    {
        if ($this->unique($key, $message)) {
            $this->messages[$key][] = $message;
        }
    }

    /**
     * Cek apakah kombinasi key dan message sudah ada atau belum.
     *
     * @param string $key
     * @param string $message
     *
     * @return bool
     */
    protected function unique($key, $message)
    {
        return (! isset($this->messages[$key]) || ! in_array($message, $this->messages[$key]));
    }

    /**
     * Cek apakah key ini memiliki message atau tidak.
     *
     * <code>
     *
     *      // Adakah message untuk atribut 'email'?
     *      return $messages->has('email');
     *
     * </code>
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return '' !== $this->first($key);
    }

    /**
     * Cek apakah message masih kosong.
     *
     * <code>
     *
     *      // Apakah message masih kosong?
     *      return $messages->any();
     *
     * </code>
     *
     * @param string $key
     *
     * @return bool
     */
    public function any()
    {
        return count($this->messages) > 0;
    }

    /**
     * Set format output default.
     *
     * <code>
     *
     *      // Set format output default baru.
     *      $messages->format('email', '<p>:message ini punyaku</p>');
     *
     * </code>
     *
     * @param string $format
     */
    public function format($format = ':message')
    {
        $this->format = $format;
    }

    /**
     * Ambil value message pertama dari key yang diberikan.
     *
     * <code>
     *
     *      // Tampilkan message pertama dari seluruh message yang ada.
     *      echo $messages->first();
     *
     *      // Tampilkan message pertama milik atribut 'email'
     *      echo $messages->first('email');
     *
     *      // Format ulang message pertama milik atribut 'email'
     *      echo $messages->first('email', '<p>:message</p>');
     *
     * </code>
     *
     * @param string $key
     * @param string $format
     *
     * @return string
     */
    public function first($key = null, $format = null)
    {
        $format = is_null($format) ? $this->format : $format;
        $messages = is_null($key) ? $this->all($format) : $this->get($key, $format);

        return (count($messages) > 0) ? $messages[0] : '';
    }

    /**
     * Ambil semua message milik key yang diberikan.
     *
     * <code>
     *
     *      // Tampilkan semua message milik atribut 'email'
     *      echo $messages->get('email');
     *
     *      // Format ulang semua message milik atribut 'email'
     *      echo $messages->get('email', '<p>:message</p>');
     *
     * </code>
     *
     * @param string $key
     * @param string $format
     *
     * @return array
     */
    public function get($key, $format = null)
    {
        $format = is_null($format) ? $this->format : $format;

        if (array_key_exists($key, $this->messages)) {
            return $this->transform($this->messages[$key], $format);
        }

        return [];
    }

    /**
     * Ambil seluruh pesan milik seluruh key yang terdaftar di collector.
     *
     * <code>
     *
     *      // Ambil seluruh pesan yang terdaftar di collector
     *      $all = $messages->all();
     *
     *      // Format ulang seluruh pesan yang terdaftar di collector
     *      $all = $messages->all('<p>:message</p>');
     *
     * </code>
     *
     * @param string $format
     *
     * @return array
     */
    public function all($format = null)
    {
        $format = is_null($format) ? $this->format : $format;
        $all = [];

        foreach ($this->messages as $messages) {
            $all = array_merge($all, $this->transform($messages, $format));
        }

        return $all;
    }

    /**
     * Format ulang array message.
     *
     * @param array  $messages
     * @param string $format
     *
     * @return array
     */
    protected function transform($messages, $format)
    {
        $messages = (array) $messages;

        foreach ($messages as $key => &$message) {
            $message = str_replace(':message', $message, $format);
        }

        return $messages;
    }
}
