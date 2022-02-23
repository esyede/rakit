<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Str
{
    /**
     * Cache snake-case.
     *
     * @var array
     */
    public static $snake = [];

    /**
     * Cache camel-case.
     *
     * @var array
     */
    public static $camel = [];

    /**
     * Cache studly-case.
     *
     * @var array
     */
    public static $studly = [];

    /**
     * Cache confing regex string.
     *
     * @var array
     */
    private static $strings = [];

    /**
     * Hitung panjang string.
     *
     * @param string $value
     *
     * @return int
     */
    public static function length($value)
    {
        return mb_strlen($value, 'UTF-8');
    }

    /**
     * Mereturn potongan string.
     *
     * @param string   $string
     * @param int      $start
     * @param int|null $length
     *
     * @return string
     */
    public static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * Buat karakter pertama string menjadi huruf besar.
     *
     * @param string $string
     *
     * @return string
     */
    public static function ucfirst($string)
    {
        return static::upper(static::substr($string, 0, 1)).static::substr($string, 1);
    }

    /**
     * Ubah string menjadi huruf kecil.
     *
     * @param string $value
     *
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Ubah string menjadi huruf besar.
     *
     * @param string $value
     *
     * @return string
     */
    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Ubah huruf pertama kata menjadi huruf besar.
     *
     * @param string $value
     *
     * @return string
     */
    public static function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Potong string sebanyak jumlah karakter yang ditentukan.
     *
     * @param string $value
     * @param int    $limit
     * @param string $end
     *
     * @return string
     */
    public static function limit($value, $limit = 100, $end = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')).$end;
    }

    /**
     * Potong string sebanyak jumlah kata yang ditentukan.
     *
     * @param string $value
     * @param int    $words
     * @param string $end
     *
     * @return string
     */
    public static function words($value, $words = 100, $end = '...')
    {
        preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $value, $matches);

        if (! isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }

        return rtrim($matches[0]).$end;
    }

    /**
     * Ubah kata menjadi bentuk tanggal (hanya inggris).
     *
     * @param string $string
     *
     * @return string
     */
    public static function singular($string)
    {
        if (empty(static::$strings)) {
            static::$strings = Config::get('strings');
        }

        if (in_array(mb_strtolower($string, 'UTF-8'), static::$strings['uncountable'])) {
            return $string;
        }

        foreach (static::$strings['irregular'] as $result => $pattern) {
            $pattern = '/'.$pattern.'$/i';

            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        foreach (static::$strings['singular'] as $pattern => $result) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        return $string;
    }

    /**
     * Ubah kata menjadi bentuk jamak (hanya inggris).
     *
     * @param string $string
     *
     * @return string
     */
    public static function plural($string)
    {
        if (empty(static::$strings)) {
            static::$strings = Config::get('strings');
        }

        if (in_array(mb_strtolower($string, 'UTF-8'), static::$strings['uncountable'])) {
            return $string;
        }

        foreach (static::$strings['irregular'] as $pattern => $result) {
            $pattern = '/'.$pattern.'$/i';

            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        foreach (static::$strings['plural'] as $pattern => $result) {
            if (preg_match($pattern, $string)) {
                return preg_replace($pattern, $result, $string);
            }
        }

        return $string;
    }

    /**
     * Pluralisasikan kata terakhir dari string studle-case (hanya inggris).
     *
     * @param string $value
     * @param int    $count
     *
     * @return string
     */
    public static function plural_studly($value, $count = 2)
    {
        $parts = preg_split('/(.)(?=[A-Z])/u', $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        $last = array_pop($parts);
        return implode('', $parts).static::plural(array_pop($parts), $count);
    }

    /**
     * Ubah string ke bentuk URL.
     *
     * @param string $value
     * @param string $separator
     *
     * @return string
     */
    public static function slug($value, $separator = '-')
    {
        $flip = ('-' === $separator) ? '_' : '-';
        $value = preg_replace('!['.preg_quote($flip).']+!u', $separator, $value);
        $value = str_replace('@', $separator.'at'.$separator, $value);
        $value = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', static::lower($value));
        $value = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $value);

        return trim($value, $separator);
    }

    /**
     * Ubah string menjadi bentuk kelas 'garis bawah'.
     *
     * @param string $value
     *
     * @return string
     */
    public static function classify($value)
    {
        return str_replace(' ', '_', static::title(str_replace(['_', '-', '.', '/'], ' ', $value)));
    }

    /**
     * Potong - potong string segmen URL.
     *
     * @param string $value
     *
     * @return array
     */
    public static function segments($value)
    {
        return array_diff(explode('/', trim($value, '/')), ['']);
    }

    /**
     * Hasilkan string acak sesuai panjang yang ditentukan.
     *
     * @param int $length
     *
     * @return string
     */
    public static function random($length = 16)
    {
        $string = '';

        while (($length2 = strlen($string)) < $length) {
            $size = $length - $length2;
            $bytes = base64_encode(static::bytes($size));
            $string .= substr(str_replace(['/', '+', '='], '', $bytes), 0, $size);
        }

        return $string;
    }

    /**
     * Hasilkan byte acak yang aman secara kriptografi.
     * Method ini diadaptasi dari https://github.com/paragonie/random-compat.
     *
     * @param int $length
     *
     * @return string
     */
    public static function bytes($length)
    {
        if (! is_int($length)) {
            throw new \InvalidArgumentException('Bytes length must be a positive integer');
        }

        if ($length < 1) {
            throw new \InvalidArgumentException('Bytes length must be greater than zero');
        }

        if ($length > PHP_INT_MAX) {
            throw new \InvalidArgumentException('Bytes length is too large');
        }

        $unix = ('/' === DS);
        $windows = ('\\' === DS);

        $bytes = false;

        // Gunakan openssl.
        $bytes = openssl_random_pseudo_bytes($length, $strong);

        if (false !== $strong && false !== $bytes) {
            if ($length === mb_strlen($bytes, '8bit')) {
                return $bytes;
            }
        }

        // Openssl gagal, coba /dev/urandom (unix)
        if ($unix) {
            $urandom = true;
            $basedir = ini_get('open_basedir');

            if (! empty($basedir)) {
                $paths = explode(PATH_SEPARATOR, strtolower($basedir));
                $urandom = ([] !== array_intersect(['/dev', '/dev/', '/dev/urandom'], $paths));
                unset($paths);
            }

            if ($urandom && @is_readable('/dev/urandom')) {
                $file = fopen('/dev/urandom', 'r');
                $read = 0;
                $local = '';

                while ($read < $length) {
                    $local .= fread($file, $length - $read);
                    $read = mb_strlen($local, '8bit');
                }

                fclose($file);
                $bytes = str_pad($bytes, $length, "\0") ^ str_pad($local, $length, "\0");
            }

            if ($read >= $length && $length === mb_strlen($bytes, '8bit')) {
                return $bytes;
            }
        }

        // /dev/urandom juga gagal, coba mcrypt
        if ($unix && ($windows || (PHP_VERSION_ID <= 50609 || PHP_VERSION_ID >= 50613))
        && extension_loaded('mcrypt')) {
            $bytes = @mcrypt_create_iv($length, (int) MCRYPT_DEV_URANDOM);

            if (false !== $bytes && $length === mb_strlen($bytes, '8bit')) {
                return $bytes;
            }
        }

        // Mcrypt juga masih saja gagal, coba CAPICOM (windows)
        if ($windows && class_exists('COM', false)) {
            try {
                $com = new \COM('CAPICOM.Utilities.1');
                $count = 0;

                do {
                    $bytes .= base64_decode((string) $com->GetRandom($length, 0));

                    if (mb_strlen($bytes, '8bit') >= $length) {
                        $bytes = (string) mb_substr($bytes, 0, $length, '8bit');
                    }

                    ++$count;
                } while ($count < $length);
            } catch (\Throwable $e) {
                // Skip error.
            } catch (\Exception $e) {
                // Skip error.
            }

            if ($bytes && is_string($bytes) && $length === mb_strlen($bytes, '8bit')) {
                return $bytes;
            }
        }

        // Tidak ada lagi yang bisa digunakan. Menyerah.
        throw new \Exception('There is no suitable CSPRNG installed on your system');
    }

    /**
     * Hasilkan integer acak yang aman secara kriptografi.
     * Method ini diadaptasi dari https://github.com/paragonie/random-compat.
     *
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    public static function integers($min, $max)
    {
        $min = (int) $min;
        $min = ($min < ~PHP_INT_MAX) ? ~PHP_INT_MAX : $min;
        $min = ($min > PHP_INT_MAX) ? PHP_INT_MAX : $min;

        $max = (int) $max;
        $max = ($max < ~PHP_INT_MAX) ? ~PHP_INT_MAX : $max;
        $max = ($max > PHP_INT_MAX) ? PHP_INT_MAX : $max;

        if ($min > $max) {
            throw new \Exception('Minimum value must be less than or equal to the maximum value');
        }

        if ($max === $min) {
            return (int) $min;
        }

        $attempts = 0;
        $bits = 0;
        $bytes = 0;
        $mask = 0;
        $shift = 0;
        $range = $max - $min;

        if (! is_int($range)) {
            $bytes = PHP_INT_SIZE;
            $mask = ~0;
        } else {
            while ($range > 0) {
                if (0 === $bits % 8) {
                    ++$bytes;
                }

                ++$bits;
                $range >>= 1;
                $mask = $mask << 1 | 1;
            }

            $shift = $min;
        }

        $val = 0;

        do {
            if ($attempts > 128) {
                throw new \Exception('RNG is broken - too many rejections');
            }

            $random = static::bytes($bytes);
            $val &= 0;

            for ($i = 0; $i < $bytes; ++$i) {
                $val |= ord($random[$i]) << ($i * 8);
            }

            $val &= $mask;
            $val += $shift;
            ++$attempts;
        } while (! is_int($val) || $val > $max || $val < $min);

        return (int) $val;
    }

    /**
     * Buat string UUID (versi 4).
     *
     * @return string
     */
    public static function uuid()
    {
        $uuid = bin2hex(static::bytes(16));
        return sprintf(
            '%08s-%04s-4%03s-%04x-%012s',
            substr($uuid, 0, 8),
            substr($uuid, 8, 4),
            substr($uuid, 13, 3),
            hexdec(substr($uuid, 16, 4)) & 0x3fff | 0x8000,
            substr($uuid, 20, 12)
        );
    }

    /**
     * Cek apakah string cocok dengan pola yang diberikan.
     *
     * @param string|array $pattern
     * @param string       $value
     *
     * @return bool
     */
    public static function is($pattern, $value)
    {
        $patterns = Arr::wrap($pattern);

        if (empty($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($pattern === $value) {
                return true;
            }

            $pattern = str_replace('\*', '.*', preg_quote($pattern, '#'));

            if (1 === preg_match('#^'.$pattern.'\z#u', $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ganti kemunculan pertama dari value yang diberikan dalam string.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    public static function replace_first($search, $replace, $subject)
    {
        if ('' === $search) {
            return $subject;
        }

        $position = strpos($subject, $search);
        return (false === $position) ? $subject : substr_replace($subject, $replace, $position, strlen($search));
    }

    public static function replace_last($search, $replace, $subject)
    {
        if ('' === $search) {
            return $subject;
        }

        $position = strrpos($subject, $search);
        return (false === $position) ? $subject : substr_replace($subject, $replace, $position, strlen($search));
    }

    /**
     * Ganti value yang diberikan dalam string secara berurutan dengan array.
     *
     * @param string $search
     * @param array  $replace
     * @param string $subject
     *
     * @return string
     */
    public static function replace_array($search, array $replace, $subject)
    {
        $segments = explode($search, $subject);
        $result = array_shift($segments);

        foreach ($segments as $segment) {
            $replacer = array_shift($replace);
            $result .= ($replacer ? $replacer : $search).$segment;
        }

        return $result;
    }

    /**
     * Dapatkan bagian dari string sebelum kemunculan pertama dari value yang diberikan.
     *
     * @param string $subject
     * @param string $search
     *
     * @return string
     */
    public static function before($subject, $search)
    {
        return ('' === $search) ? $subject : explode($search, $subject)[0];
    }

    /**
     * Mereturn sisa string setelah kemunculan pertama dari value yang diberikan.
     *
     * @param string $subject
     * @param string $search
     *
     * @return string
     */
    public static function after($subject, $search)
    {
        return ('' === $search) ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    /**
     * Ubah string menjadi camel-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function camel($value)
    {
        if (isset(static::$camel[$value])) {
            return static::$camel[$value];
        }

        static::$camel[$value] = lcfirst(static::studly($value));
        return static::$camel[$value];
    }

    /**
     * Ubah string menjadi studly-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function studly($value)
    {
        $key = $value;

        if (isset(static::$studly[$key])) {
            return static::$studly[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        static::$studly[$key] = str_replace(' ', '', $value);

        return static::$studly[$key];
    }

    /**
     * Ubah string menjadi kebab-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function kebab($value)
    {
        return static::snake($value, '-');
    }

    /**
     * Ubah string menjadi snake-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function snake($value, $delimiter = '_')
    {
        $key = $value;

        if (isset(static::$snake[$key][$delimiter])) {
            return static::$snake[$key][$delimiter];
        }

        $chars = static::characterify($value);
        $lowercased = is_string($chars) && '' !== $chars && ! preg_match('/[^a-z]/', $chars);

        if (! $lowercased) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        static::$snake[$key][$delimiter] = $value;
        return static::$snake[$key][$delimiter];
    }

    /**
     * Tentukan apakah string yang diberikan berisi substring yang diberikan.
     *
     * @param string $haystack
     * @param string $needles
     *
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        $needles = (array) $needles;

        foreach ($needles as $needle) {
            if ('' !== $needle && false !== mb_strpos($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tentukan apakah string yang diberikan berisi semua nilai array.
     *
     * @param string $haystack
     * @param array  $needles
     *
     * @return bool
     */
    public static function contains_all($haystack, array $needles)
    {
        foreach ($needles as $needle) {
            if (! static::contains($haystack, $needle)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Awali string dengan sebuah instance dari nilai yang diberikan.
     *
     * @param string $value
     * @param string $prefix
     *
     * @return string
     */
    public static function start($value, $prefix)
    {
        return $prefix.preg_replace('/^(?:'.preg_quote($prefix, '/').')+/u', '', $value);
    }

    /**
     * Tentukan apakah string yang diberikan dimulai dengan substring yang diberikan.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function starts_with($haystack, $needle)
    {
        return ('' !== (string) $needle && 0 === strncmp($haystack, $needle, strlen($needle)));
    }

    /**
     * Tentukan apakah string yang diberikan diakhiri dengan substring yang diberikan.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function ends_with($haystack, $needle)
    {
        return ('' !== $needle && ((string) $needle === substr($haystack, -strlen($needle))));
    }

    /**
     * Akhiri string dengan sebuah instance dari nilai yang diberikan.
     *
     * @param string $value
     * @param string $cap
     *
     * @return string
     */
    public static function finish($value, $cap)
    {
        return preg_replace('/(?:'.preg_quote($cap, '/').')+$/u', '', $value).$cap;
    }

    /**
     * Uraikan string berpola callback menjadi array.
     *
     * @param string     $callback
     * @param mixed|null $default
     *
     * @return array
     */
    public static function parse_callback($callback, $default = null)
    {
        return static::contains($callback, '@') ? explode('@', $callback, 2) : [$callback, $default];
    }

    /**
     * Konversikan integer ke char mengikuti aturan ctype.
     *
     * @param string|int $value
     *
     * @return mixed
     */
    public static function characterify($value)
    {
        if (! is_int($value)) {
            return $value;
        }

        if ($value < -128 || $value > 255) {
            return (string) $value;
        }

        if ($value < 0) {
            $value += 256;
        }

        return chr($value);
    }
}
