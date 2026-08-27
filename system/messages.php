<?php

namespace System;

defined('DS') or exit('No direct access.');

class Messages implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
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
        return (! isset($this->messages[$key]) || ! in_array($message, $this->messages[$key]));
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
        return '' !== $key && ! is_null($key);
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

    /**
     * Merge another bag or array of messages into this one.
     *
     * @param Messages|array $messages
     *
     * @return $this
     */
    public function merge($messages)
    {
        $messages = ($messages instanceof self) ? $messages->messages : (array) $messages;

        foreach ($messages as $key => $items) {
            foreach ((array) $items as $message) {
                $this->add($key, $message);
            }
        }

        return $this;
    }

    /**
     * Drop every message of the given key.
     *
     * @param string $key
     *
     * @return $this
     */
    public function forget($key)
    {
        unset($this->messages[$key]);

        return $this;
    }

    /**
     * Get every key that holds a message.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->messages);
    }

    /**
     * Check whether at least one of the given keys has a message.
     *
     * @param array|string $keys
     *
     * @return bool
     */
    public function has_any($keys = [])
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the bag holds no message at all.
     *
     * @return bool
     */
    public function is_empty()
    {
        return ! $this->any();
    }

    /**
     * Check whether the bag holds at least one message.
     *
     * @return bool
     */
    public function is_not_empty()
    {
        return $this->any();
    }

    /**
     * Get the raw messages, keyed by their field name.
     *
     * @return array
     */
    public function to_array()
    {
        return $this->messages;
    }

    /**
     * Convert the bag into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->to_array();
    }

    /**
     * Convert the bag into JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function to_json($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get the number of keys that hold a message.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->messages);
    }

    /**
     * Get an iterator for the messages.
     *
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->messages);
    }

    /**
     * Check whether a key holds a message.
     *
     * @param mixed $key
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return isset($this->messages[$key]);
    }

    /**
     * Get every message of the given key.
     *
     * @param mixed $key
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Add a message to the given key.
     *
     * @param mixed $key
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        $this->add($key, $value);
    }

    /**
     * Drop every message of the given key.
     *
     * @param mixed $key
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        $this->forget($key);
    }
}
