<?php

namespace System\Foundation\Carbon;

class Translator
{
    protected $catalogues = [];
    protected $formatter;
    protected $locale;

    private $fallbackLocales = [];
    private $loaders = [];
    private $resources = [];
    private $selector;

    protected static $singleton;
    protected static $messages = [];

    public static function get($locale = 'en')
    {
        if (static::$singleton === null) {
            static::$singleton = new static(($locale === 'id') ? 'id' : 'en');
        }

        return static::$singleton;
    }

    public function __construct($locale)
    {
        $this->locale = ($locale === 'id') ? 'id' : 'en';
    }

    public function addResource($format, $resource, $locale, $domain = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        $this->assertValidLocale($locale);
        $this->resources[$locale][] = array($format, $resource, $domain);

        if (in_array($locale, $this->fallbackLocales)) {
            $this->catalogues = [];
        } else {
            unset($this->catalogues[$locale]);
        }
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setFallbackLocales(array $locales)
    {
        $this->catalogues = [];

        foreach ($locales as $locale) {
            $this->assertValidLocale($locale);
        }

        $this->fallbackLocales = $locales;
    }

    public function getFallbackLocales()
    {
        return $this->fallbackLocales;
    }

    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        return strtr($this->getCatalogue($locale)->get((string) $id, $domain), $parameters);
    }

    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        if (null === $domain) {
            $domain = 'messages';
        }

        $id = (string) $id;
        $catalogue = $this->getCatalogue($locale);
        $locale = $catalogue->getLocale();
        while (!$catalogue->defines($id, $domain)) {
            if ($cat = $catalogue->getFallbackCatalogue()) {
                $catalogue = $cat;
                $locale = $catalogue->getLocale();
            } else {
                break;
            }
        }

        return strtr($this->selector->choose($catalogue->get($id, $domain), (int) $number, $locale), $parameters);
    }

    public function getCatalogue($locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        } else {
            $this->assertValidLocale($locale);
        }

        if (!isset($this->catalogues[$locale])) {
            $this->loadCatalogue($locale);
        }

        return $this->catalogues[$locale];
    }

    protected function getLoaders()
    {
        return $this->loaders;
    }

    protected function loadCatalogue($locale)
    {
        $this->initializeCatalogue($locale);
    }

    protected function initializeCatalogue($locale)
    {
        $this->assertValidLocale($locale);

        try {
            $this->doLoadCatalogue($locale);
        } catch (\Exception $e) {
            if (!$this->computeFallbackLocales($locale)) {
                throw $e;
            }
        }
        $this->loadFallbackCatalogues($locale);
    }

    private function doLoadCatalogue($locale)
    {
        $this->catalogues[$locale] = $locale;

        if (isset($this->resources[$locale])) {
            foreach ($this->resources[$locale] as $resource) {
                if (!isset($this->loaders[$resource[0]])) {
                    throw new \Exception(sprintf('The "%s" translation loader is not registered.', $resource[0]));
                }
                $this->catalogues[$locale]->addCatalogue($this->loaders[$resource[0]]->load($resource[1], $locale, $resource[2]));
            }
        }
    }

    private function loadFallbackCatalogues($locale)
    {
        $current = $this->catalogues[$locale];
    }

    protected function computeFallbackLocales($locale)
    {
        $locales = [];
        foreach ($this->fallbackLocales as $fallback) {
            if ($fallback === $locale) {
                continue;
            }

            $locales[] = $fallback;
        }

        if (false !== strrchr($locale, '_')) {
            array_unshift($locales, substr($locale, 0, -\strlen(strrchr($locale, '_'))));
        }

        return array_unique($locales);
    }

    protected function assertValidLocale($locale)
    {
        if (1 !== preg_match('/^[a-z0-9@_\\.\\-]*$/i', $locale)) {
            throw new \Exception(sprintf('Invalid "%s" locale.', $locale));
        }
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
            return '_' . call_user_func(strlen($matches[1]) > 2 ? 'ucfirst' : 'strtoupper', $matches[1]);
        }, strtolower($locale));

        if ($this->loadMessagesFromFile($locale)) {
            $this->locale = $locale;
            return true;
        }

        return false;
    }
}
