<?php

class Number
{
    public static function format($number, $precision = null, $max_precision = null, $locale = null)
    {
        static::intl_loaded();
        $fmt = new \NumberFormatter($locale ? $locale : static::$locale, \NumberFormatter::DECIMAL);

        if (!is_null($max_precision)) {
            $fmt->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $max_precision);
        } elseif (!is_null($precision)) {
            $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, $precision);
        }

        return $fmt->format($number);
    }

    public static function spell($number, $locale = null)
    {
        static::intl_loaded();
        return (new \NumberFormatter($locale ? $locale : static::$locale, \NumberFormatter::SPELLOUT))->format($number);
    }

    public static function ordinal($number, $locale = null)
    {
        static::intl_loaded();
        $fmt = new \NumberFormatter($locale ? $locale : static::$locale, \NumberFormatter::ORDINAL);
        return $fmt->format($number);
    }

    public static function percentage($number, $precision = 0, $max_precision = null, $locale = null)
    {
        static::intl_loaded();
        $fmt = new \NumberFormatter($locale ? $locale : static::$locale, \NumberFormatter::PERCENT);

        if (!is_null($max_precision)) {
            $fmt->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $max_precision);
        } else {
            $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, $precision);
        }

        return $fmt->format($number / 100);
    }

    public static function currency($number, $in = 'USD', $locale = null)
    {
        static::intl_loaded();
        $fmt = new \NumberFormatter($locale ? $locale : static::$locale, \NumberFormatter::CURRENCY);
        return $fmt->formatCurrency($number, $in);
    }

    public static function filesize($bytes, $precision = 0, $max_precision = null)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        for ($i = 0; ($bytes / 1024) > 0.9 && ($i < count($units) - 1); $i++) {
            $bytes /= 1024;
        }

        return sprintf('%s %s', static::format($bytes, $precision, $max_precision), $units[$i]);
    }

    public static function humanize($number, $precision = 0, $max_precision = null)
    {
        $units = [3 => 'thousand', 6 => 'million', 9 => 'billion', 12 => 'trillion', 15 => 'quadrillion'];

        switch (true) {
            case $number === 0:
                return '0';

            case ($number < 0):
                return sprintf('-%s', static::humanize(abs($number), $precision, $max_precision));

            case ($number >= 1e15):
                return sprintf('%s quadrillion', static::humanize($number / 1e15, $precision, $max_precision));
        }

        $exponent = floor(log10($number));
        $display = $exponent - ($exponent % 3);
        $number /= pow(10, $display);
        $formatted = static::format($number, $precision, $max_precision);

        return trim(sprintf('%s %s', $formatted, isset($units[$display]) ? $units[$display] : ''));
    }

    public static function with_locale($locale, callable $callback)
    {
        $old = static::$locale;
        static::use_locale($locale);
        return tap($callback(), function () {
            return static::use_locale($old);
        });
    }

    public static function use_locale($locale)
    {
        static::$locale = $locale;
    }

    protected static function intl_loaded()
    {
        if (!extension_loaded('intl')) {
            throw new \Exception('The "intl" PHP extension is required to use this method.');
        }
    }
}
