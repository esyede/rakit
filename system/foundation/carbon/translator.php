<?php

namespace System\Foundation\Carbon;

use Symfony\Component\Translation;

class Translator extends Translation\Translator
{
    protected static $singleton;
    protected static $messages = [];

    public static function get($locale = null)
    {
        if (static::$singleton === null) {
            static::$singleton = new static($locale ?: 'en');
        }

        return static::$singleton;
    }

    public function __construct($locale, Translation\Formatter\MessageFormatterInterface $formatter = null, $cacheDir = null, $debug = false)
    {
        $this->addLoader('array', new Translation\Loader\ArrayLoader());
        parent::__construct($locale, $formatter, $cacheDir, $debug);
    }

    public function resetMessages($locale = null)
    {
        if ($locale === null) {
            static::$messages = [];
            return true;
        }

        if (is_file($filename = __DIR__ . '/Lang/' . $locale . '.php')) {
            static::$messages[$locale] = require $filename;
            $this->addResource('array', static::$messages[$locale], $locale);
            return true;
        }

        return false;
    }

    protected function loadMessagesFromFile($locale)
    {
        return (isset(static::$messages[$locale])) ? true : $this->resetMessages($locale);
    }

    public function setMessages($locale, $messages)
    {
        $this->loadMessagesFromFile($locale);
        $this->addResource('array', $messages, $locale);
        static::$messages[$locale] = array_merge(
            isset(static::$messages[$locale]) ? static::$messages[$locale] : [],
            $messages
        );

        return $this;
    }

    public function getMessages($locale = null)
    {
        return ($locale === null) ? static::$messages : static::$messages[$locale];
    }

    public function setLocale($locale)
    {
        $locale = preg_replace_callback('/[-_]([a-z]{2,})/', function ($matches) {
            return '_'.call_user_func(strlen($matches[1]) > 2 ? 'ucfirst' : 'strtoupper', $matches[1]);
        }, strtolower($locale));

        if ($this->loadMessagesFromFile($locale)) {
            parent::setLocale($locale);
            return true;
        }

        return false;
    }
}
