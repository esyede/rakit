<?php

namespace System;

defined('DS') or exit('No direct access.');

class Messages
{
    /**
     * Contains all registered messages.
     *
     * @var array
     */
    public $messages;

    /**
     * The default format for output.
     *
     * @var string
     */
    public $format = ':message';

    /**
     * Create a new Messages instance.
     *
     * @param array $messages
     */
    public function __construct(array $messages = [])
    {
        $this->messages = $messages;
    }

    /**
     * Add a message to the collector.
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
     * Check if a combination of key and message already exists.
     *
     * @param string $key
     * @param string $message
     *
     * @return bool
     */
    protected function unique($key, $message)
    {
        return (!isset($this->messages[$key]) || !in_array($message, $this->messages[$key]));
    }

    /**
     * Check if a key has any messages.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        $key = $this->first($key);
        return '' !== $key && !is_null($key);
    }

    /**
     * Check if message is empty.
     *
     * @return bool
     */
    public function any()
    {
        return count($this->messages) > 0;
    }

    /**
     * Set the default output format.
     *
     * @param string $format
     */
    public function format($format = ':message')
    {
        $this->format = $format;
    }

    /**
     * Get the first message from the given key.
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
     * Get all messages from the given key.
     *
     * @param string $key
     * @param string $format
     *
     * @return array
     */
    public function get($key, $format = null)
    {
        $format = is_null($format) ? $this->format : $format;
        return array_key_exists($key, $this->messages) ? $this->transform($this->messages[$key], $format) : [];
    }

    /**
     * Get all messages from all keys.
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
     * Re-format array message.
     *
     * @param array  $messages
     * @param string $format
     *
     * @return array
     */
    protected function transform(array $messages, $format)
    {
        foreach ($messages as $key => &$message) {
            $message = str_replace(':message', $message, $format);
        }

        return $messages;
    }
}
