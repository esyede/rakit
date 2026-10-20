<?php

namespace System;

defined('DS') or exit('No direct access.');

class Str
{
    /**
     * Contains additional methods from user.
     *
     * @var array
     */
    public static $macros = [];

    /**
     * Contains cache of snake-cased strings.
     *
     * @var array
     */
    public static $snake = [];

    /**
     * Contains cache of camel-cased strings.
     *
     * @var array
     */
    public static $camel = [];

    /**
     * Contains cache of studly-cased strings.
     *
     * @var array
     */
    public static $studly = [];

    /**
     * Contains cache of regex strings.
     *
     * @var array
     */
    private static $strings = [];

    /**
     * Bucket for ULID.
     *
     * @var array
     */
    private static $ulids = ['time' => 0, 'chars' => []];

    /**
     * Bucket for CUID.
     *
     * @var array
     */
    private static $cuids = ['counter' => 0, 'fingerprint' => null];

    /**
     * Count the length of a string.
     *
     * @param string $value
     *
     * @return int
     */
    public static function length($value)
    {
        return mb_strlen((string) $value, 'UTF-8');
    }

    /**
     * Return a substring of a string.
     *
     * @param string   $string
     * @param int      $start
     * @param int|null $length
     *
     * @return string
     */
    public static function substr($string, $start, $length = null)
    {
        $string = (string) $string;
        // PHP before 5.4.8 treats a NULL length as 0 instead of "up to the end".
        $length = is_null($length) ? mb_strlen($string, 'UTF-8') : $length;

        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * Make the first character of a string uppercase.
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
     * Make the string lowercased.
     *
     * @param string $value
     *
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower((string) $value, 'UTF-8');
    }

    /**
     * Make the string uppercased.
     *
     * @param string $value
     *
     * @return string
     */
    public static function upper($value)
    {
        return mb_strtoupper((string) $value, 'UTF-8');
    }

    /**
     * Make the first character of each word uppercase.
     *
     * @param string $value
     *
     * @return string
     */
    public static function title($value)
    {
        return mb_convert_case((string) $value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Cut a string to a given length.
     *
     * @param string $value
     * @param int    $limit
     * @param string $end
     *
     * @return string
     */
    public static function limit($value, $limit = 100, $end = '...')
    {
        $value = (string) $value;

        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')).$end;
    }

    /**
     * Trim whitespaces, ASCII and multi-byte whitespaces (e.g. Whitespace from Microsoft Word).
     *
     * @param string $value
     *
     * @return string
     */
    public static function trim($value)
    {
        return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', (string) $value);
    }

    /**
     * Cut a string to a given length by words.
     *
     * @param string $value
     * @param int    $words
     * @param string $end
     *
     * @return string
     */
    public static function words($value, $words = 100, $end = '...')
    {
        $value = (string) $value;
        $words = (int) $words;

        if ($words < 1) {
            return ('' === $value) ? $value : $end;
        }

        preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $value, $matches);

        if (! isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }

        return rtrim($matches[0]).$end;
    }

    /**
     * Transform a string to singular form (english only).
     *
     * @param string $string
     *
     * @return string
     */
    public static function singular($string)
    {
        $string = (string) $string;

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
     * Transform a string to plural form (english only).
     *
     * @param string $string
     * @param int    $count
     *
     * @return string
     */
    public static function plural($string, $count = 2)
    {
        $string = (string) $string;

        if (1 === (int) abs($count)) {
            return $string;
        }

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
     * Pluralize the last word of the studly-case string (english only).
     *
     * @param string $value
     * @param int    $count
     *
     * @return string
     */
    public static function plural_studly($value, $count = 2)
    {
        $parts = preg_split('/(.)(?=[A-Z])/u', (string) $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        $last = array_pop($parts);

        return implode('', $parts).static::plural($last, $count);
    }

    /**
     * Make an URL friendly slug.
     *
     * @param string $value
     * @param string $separator
     *
     * @return string
     */
    public static function slug($value, $separator = '-')
    {
        $value = (string) $value;
        $flip = ('-' === $separator) ? '_' : '-';
        $value = preg_replace('!['.preg_quote($flip).']+!u', $separator, $value);
        $value = str_replace('@', $separator.'at'.$separator, $value);
        $value = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', static::lower($value));
        $value = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $value);

        return trim($value, $separator);
    }

    /**
     * Transform a string into a PSR-0 class name.
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
     * Remove accents from a string.
     *
     * @param string $value
     *
     * @return string
     */
    public static function accentless($value)
    {
        $accents = [
            '/ä|æ|ǽ/' => 'ae', '/ö|œ/' => 'oe', '/ü/' => 'ue', '/Ä/' => 'Ae',
            '/Ü/' => 'Ue', '/Ö/' => 'Oe',
            '/À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ|Α|Ά|Ả|Ạ|Ầ|Ẫ|Ẩ|Ậ|Ằ|Ắ|Ẵ|Ẳ|Ặ|А/' => 'A',
            '/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª|α|ά|ả|ạ|ầ|ấ|ẫ|ẩ|ậ|ằ|ắ|ẵ|ẳ|ặ|а/' => 'a',
            '/Б/' => 'B', '/б/' => 'b', '/Ç|Ć|Ĉ|Ċ|Č/' => 'C', '/ç|ć|ĉ|ċ|č/' => 'c',
            '/Д/' => 'D', '/д/' => 'd', '/Ð|Ď|Đ|Δ/' => 'Dj', '/ð|ď|đ|δ/' => 'dj',
            '/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě|Ε|Έ|Ẽ|Ẻ|Ẹ|Ề|Ế|Ễ|Ể|Ệ|Е|Э/' => 'E',
            '/è|é|ê|ë|ē|ĕ|ė|ę|ě|έ|ε|ẽ|ẻ|ẹ|ề|ế|ễ|ể|ệ|е|э/' => 'e', '/Ф/' => 'F', '/ф/' => 'f',
            '/Ĝ|Ğ|Ġ|Ģ|Γ|Г|Ґ/' => 'G', '/ĝ|ğ|ġ|ģ|γ|г|ґ/' => 'g', '/Ĥ|Ħ/' => 'H', '/ĥ|ħ/' => 'h',
            '/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ|Η|Ή|Ί|Ι|Ϊ|Ỉ|Ị|И|Ы/' => 'I',
            '/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı|η|ή|ί|ι|ϊ|ỉ|ị|и|ы|ї/' => 'i', '/Ĵ/' => 'J',
            '/ĵ/' => 'j', '/Ķ|Κ|К/' => 'K', '/ķ|κ|к/' => 'k',
            '/Ĺ|Ļ|Ľ|Ŀ|Ł|Λ|Л/' => 'L', '/ĺ|ļ|ľ|ŀ|ł|λ|л/' => 'l', '/М/' => 'M', '/м/' => 'm',
            '/Ñ|Ń|Ņ|Ň|Ν|Н/' => 'N', '/ñ|ń|ņ|ň|ŉ|ν|н/' => 'n',
            '/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ|Ο|Ό|Ω|Ώ|Ỏ|Ọ|Ồ|Ố|Ỗ|Ổ|Ộ|Ờ|Ớ|Ỡ|Ở|Ợ|О/' => 'O',
            '/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º|ο|ό|ω|ώ|ỏ|ọ|ồ|ố|ỗ|ổ|ộ|ờ|ớ|ỡ|ở|ợ|о/' => 'o',
            '/П/' => 'P', '/п/' => 'p', '/Ŕ|Ŗ|Ř|Ρ|Р/' => 'R', '/ŕ|ŗ|ř|ρ|р/' => 'r',
            '/Ś|Ŝ|Ş|Ș|Š|Σ|С/' => 'S', '/ś|ŝ|ş|ș|š|ſ|σ|ς|с/' => 's',
            '/Ț|Ţ|Ť|Ŧ|τ|Т/' => 'T', '/ț|ţ|ť|ŧ|т/' => 't',
            '/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ|Ũ|Ủ|Ụ|Ừ|Ứ|Ữ|Ử|Ự|У/' => 'U',
            '/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ|υ|ύ|ϋ|ủ|ụ|ừ|ứ|ữ|ử|ự|у/' => 'u',
            '/Ƴ|Ɏ|Ỵ|Ẏ|Ӳ|Ӯ|Ў|Ý|Ÿ|Ŷ|Υ|Ύ|Ϋ|Ỳ|Ỹ|Ỷ|Ỵ|Й/' => 'Y',
            '/ẙ|ʏ|ƴ|ɏ|ỵ|ẏ|ӳ|ӯ|ў|ý|ÿ|ŷ|ỳ|ỹ|ỷ|ỵ|й/' => 'y', '/В/' => 'V', '/в/' => 'v',
            '/Ŵ/' => 'W', '/ŵ/' => 'w', '/Ź|Ż|Ž|Ζ|З/' => 'Z', '/ź|ż|ž|ζ|з/' => 'z',
            '/Æ|Ǽ/' => 'AE', '/ß/' => 'ss', '/Ĳ/' => 'IJ', '/ĳ/' => 'ij','/Œ/' => 'OE',
            '/ƒ/' => 'f', '/ξ/' => 'ks', '/π/' => 'p', '/β/' => 'v', '/μ/' => 'm',
            '/ψ/' => 'ps', '/Ё/' => 'Yo', '/ё/' => 'yo', '/Є/' => 'Ye', '/є/' => 'ye',
            '/Ї/' => 'Yi', '/Ж/' => 'Zh', '/ж/' => 'zh', '/Х/' => 'Kh', '/х/' => 'kh',
            '/Ц/' => 'Ts', '/ц/' => 'ts', '/Ч/' => 'Ch', '/ч/' => 'ch', '/Ш/' => 'Sh',
            '/ш/' => 'sh', '/Щ/' => 'Shch', '/щ/' => 'shch', '/Ъ|ъ|Ь|ь/' => '',
            '/Ю/' => 'Yu', '/ю/' => 'yu', '/Я/' => 'Ya', '/я/' => 'ya',
        ];
        return preg_replace(array_keys($accents), array_values($accents), (string) $value);
    }

    /**
     * Split a string into segments.
     *
     * @param string $value
     *
     * @return array
     */
    public static function segments($value)
    {
        return array_values(array_diff(explode('/', trim((string) $value, '/')), ['']));
    }

    /**
     * Generate a random string.
     *
     * @param int $length
     *
     * @return string
     */
    public static function random($length = 16)
    {
        $string = '';

        while (($length2 = mb_strlen($string, '8bit')) < $length) {
            $size = $length - $length2;
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode(static::bytes($size))), 0, $size);
        }

        return $string;
    }

    /**
     * Generate a random password.
     *
     * @param int  $length
     * @param bool $letters
     * @param bool $numbers
     * @param bool $symbols
     * @param bool $spaces
     *
     * @return string
     */
    public static function password(
        $length = 32,
        $letters = true,
        $numbers = true,
        $symbols = true,
        $spaces = false
    ) {
        $chars = array_merge(
            [],
            $letters ? [
                'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
                'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
                'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G',
                'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
                'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            ] : [],
            $numbers ? ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'] : [],
            $symbols ? [
                '~', '!', '#', '$', '%', '^', '&', '*', '(', ')', '-', '_', '.',
                ',', '<', '>', '?', '/', '\\', '{', '}', '[', ']', '|', ':', ';',
            ] : [],
            $spaces ? [' '] : []
        );

        $max = count($chars) - 1;
        $result = '';

        if ($max < 0 || $length < 1) {
            return $result;
        }

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[static::integers(0, $max)];
        }

        return $result;
    }

    /**
     * Generate a cryptographically secure random bytes.
     * Adopted from https://github.com/paragonie/random-compat.
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

        // The OS CSPRNG, on PHP 7+.
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }

        // Use openssl.
        $bytes = openssl_random_pseudo_bytes($length, $strong);

        if (false !== $strong && false !== $bytes) {
            if ($length === mb_strlen((string) $bytes, '8bit')) {
                return $bytes;
            }
        }

        if ($unix) {
            $urandom = true;
            $basedir = ini_get('open_basedir');

            if (! empty($basedir)) {
                $paths = explode(PATH_SEPARATOR, strtolower((string) $basedir));
                $urandom = ([] !== array_intersect(['/dev', '/dev/', '/dev/urandom'], $paths));
                unset($paths);
            }

            $read = 0;

            if ($urandom && @is_readable('/dev/urandom')) {
                $file = fopen('/dev/urandom', 'r');
                $local = '';

                while ($read < $length) {
                    $local .= fread($file, $length - $read);
                    $read = mb_strlen((string) $local, '8bit');
                }

                fclose($file);
                $bytes = str_pad($bytes, $length, "\0") ^ str_pad($local, $length, "\0");
            }

            if ($read >= $length && $length === mb_strlen((string) $bytes, '8bit')) {
                return $bytes;
            }
        }

        if ($windows && class_exists('\COM', false)) {
            try {
                $com = new \COM('CAPICOM.Utilities.1');
                $count = 0;

                do {
                    $bytes .= base64_decode((string) $com->GetRandom($length, 0));

                    if (mb_strlen($bytes, '8bit') >= $length) {
                        $bytes = mb_substr($bytes, 0, $length, '8bit');
                    }

                    ++$count;
                } while ($count < $length);
            } catch (\Throwable $e) {
                $bytes = false;
            } catch (\Exception $e) {
                $bytes = false;
            }

            if ($bytes && is_string($bytes) && $length === mb_strlen($bytes, '8bit')) {
                return $bytes;
            }
        }

        throw new \Exception('There is no suitable CSPRNG installed on your system');
    }

    /**
     * Generate a cryptographically secure random integer.
     * Adopted from https://github.com/paragonie/random-compat.
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

        $attempts = $bits = $bytes = $mask = $shift = 0;
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
     * Generate a UUID (version 4) string.
     *
     * @return string
     */
    public static function uuid()
    {
        $bytes = static::bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40); // version 4
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); // RFC 4122 variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Generate a ULID string.
     *
     * @param bool $lowercase
     *
     * @return string
     */
    public static function ulid($lowercase = false)
    {
        $milliseconds = (int) (microtime(true) * 1000);
        $duplicate = $milliseconds === static::$ulids['time'];
        static::$ulids['time'] = $milliseconds;

        $characters = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $time = '';
        $random = '';

        for ($i = 9; $i >= 0; $i--) {
            $mod = $milliseconds % 32;
            $time = $characters[$mod].$time;
            $milliseconds = ($milliseconds - $mod) / 32;
        }

        if (! $duplicate) {
            for ($i = 0; $i < 16; $i++) {
                static::$ulids['chars'][$i] = static::integers(0, 31);
            }
        } else {
            for ($i = 15; $i >= 0 && static::$ulids['chars'][$i] === 31; $i--) {
                static::$ulids['chars'][$i] = 0;
            }

            if ($i < 0) {
                for ($i = 0; $i < 16; $i++) {
                    static::$ulids['chars'][$i] = static::integers(0, 31);
                }
            } else {
                static::$ulids['chars'][$i]++;
            }
        }

        for ($i = 0; $i < 16; $i++) {
            $random .= $characters[static::$ulids['chars'][$i]];
        }

        return $lowercase ? strtolower($time.$random) : $time.$random;
    }

    /**
     * Generate a CUID (Collision-resistant Unique Identifier).
     *
     * @return string
     */
    public static function cuid()
    {
        $result = 'c'.str_pad(base_convert((string) floor(microtime(true) * 1000), 10, 36), 8, '0', STR_PAD_LEFT);
        static::$cuids['counter']++;
        static::$cuids['counter'] = (static::$cuids['counter'] > 1679615) ? 0 : static::$cuids['counter'];
        $result .= str_pad(base_convert((string) static::$cuids['counter'], 10, 36), 4, '0', STR_PAD_LEFT);

        if (static::$cuids['fingerprint'] === null) {
            $pid = function_exists('getmypid') ? getmypid() : static::integers(1, 32768);
            $dec = hexdec(substr(md5((gethostname() ?: 'unknown').$pid.bin2hex(static::bytes(2))), 0, 8));
            static::$cuids['fingerprint'] = str_pad(substr(base_convert((string) $dec, 10, 36), 0, 4), 4, '0', STR_PAD_LEFT);
        }

        $result .= static::$cuids['fingerprint'];
        $result .= str_pad(substr(base_convert((string) hexdec(bin2hex(static::bytes(2))), 10, 36), 0, 4), 4, '0', STR_PAD_LEFT);
        $result .= str_pad(substr(base_convert((string) hexdec(bin2hex(static::bytes(2))), 10, 36), 0, 4), 4, '0', STR_PAD_LEFT);

        return $result;
    }

    /**
     * Generate a Nano ID string.
     *
     * @param int         $size
     * @param string|null $characters
     *
     * @return string|null
     */
    public static function nanoid($size = 21, $characters = null)
    {
        $size = (int) $size;

        if ($size > 21 || $size < 8) {
            throw new \Exception('The size parameter should be between 8 to 21.');
        }

        $default = 'useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict';
        $characters = (! is_string($characters) || empty($characters)) ? $default : $characters;
        $mask = (2 << (int) (log(strlen($characters) - 1) / M_LN2)) - 1;
        $step = (int) ceil(1.6 * $mask * $size / strlen($characters));
        $result = '';

        while (true) {
            $bytes = unpack('C*', static::bytes($step));

            foreach ($bytes as $byte) {
                $byte &= $mask;

                if (isset($characters[$byte])) {
                    $result .= $characters[$byte];

                    if (strlen($result) === $size) {
                        return $result;
                    }
                }
            }
        }
    }

    /**
     * Generate dummy lorem-ipsum text.
     *
     * @param int  $count
     * @param int  $max
     * @param bool $standard
     *
     * @return string
     */
    public static function lorem($count = 1, $max = 20, $standard = true)
    {
        $result = $standard ? 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, '
            . 'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.' : '';
        $pool = explode(
            ' ',
            'a ab ad accusamus adipisci alias aliquam amet animi aperiam architecto asperiores aspernatur '
            . 'assumenda at atque aut beatae blanditiis cillum commodi consequatur corporis corrupti culpa '
            . 'cum cupiditate debitis delectus deleniti deserunt dicta dignissimos distinctio dolor ducimus '
            . 'duis ea eaque earum eius eligendi enim eos error esse est eum eveniet ex excepteur exercitationem '
            . 'expedita explicabo facere facilis fugiat harum hic id illum impedit in incidunt ipsa iste itaque '
            . 'iure iusto laborum laudantium libero magnam maiores maxime minim minus modi molestiae mollitia '
            . 'nam natus necessitatibus nemo neque nesciunt nihil nisi nobis non nostrum nulla numquam occaecati '
            . 'odio officia omnis optio pariatur perferendis perspiciatis placeat porro possimus praesentium '
            . 'proident quae quia quibus quo ratione recusandae reiciendis rem repellat reprehenderit repudiandae '
            . 'rerum saepe sapiente sequi similique sint soluta suscipit tempora tenetur totam ut ullam unde vel '
            . 'veniam vero vitae voluptas'
        );

        $max = ($max <= 3) ? 4 : $max;
        $count = ($count < 1) ? 1 : (($count > 2147483646) ? 2147483646 : $count);

        for ($i = 0, $add = ($count - (int) $standard); $i < $add; $i++) {
            shuffle($pool);
            $words = array_slice($pool, 0, mt_rand(3, $max));
            $result .= ((! $standard && $i === 0) ? '' : ' ').ucfirst(implode(' ', $words)).'.';
        }

        return $result;
    }

    /**
     * Check if string matches given pattern.
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

            $pattern = str_replace('\*', '.*', preg_quote($pattern, '/'));

            if (1 === preg_match('/^'.$pattern.'\z/u', $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace the first occurrence of the given value in the string.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    public static function replace_first($search, $replace, $subject)
    {
        $subject = (string) $subject;
        $search = (string) $search;

        if ('' === $search) {
            return $subject;
        }

        $position = strpos($subject, $search);
        return (false === $position)
            ? $subject
            : substr_replace($subject, $replace, $position, mb_strlen($search, '8bit'));
    }

    /**
     * Replace the last occurrence of the given value in the string.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    public static function replace_last($search, $replace, $subject)
    {
        $subject = (string) $subject;
        $search = (string) $search;

        if ('' === $search) {
            return $subject;
        }

        $position = strrpos($subject, $search);
        return (false === $position)
            ? $subject
            : substr_replace($subject, $replace, $position, mb_strlen($search, '8bit'));
    }

    /**
     * Replace the given value in the string with array.
     *
     * @param string $search
     * @param array  $replace
     * @param string $subject
     *
     * @return string
     */
    public static function replace_array($search, array $replace, $subject)
    {
        $search = (string) $search;
        $subject = (string) $subject;

        if ('' === $search) {
            return $subject;
        }

        $segments = explode($search, $subject);
        $result = array_shift($segments);

        foreach ($segments as $segment) {
            $replacer = (count($replace) > 0) ? array_shift($replace) : null;
            $result .= ((null === $replacer) ? $search : $replacer).$segment;
        }

        return $result;
    }

    /**
     * Censor some letters in word / sentence.
     *
     * @param string $string
     * @param string $replacement
     *
     * @return string
     */
    public static function censor($string, $replacement = '*')
    {
        $string = (string) $string;
        $len = strlen($string) - (int) floor(strlen($string) / 2);
        return substr_replace($string, str_repeat($replacement, $len), (int) floor($len / 2), $len);
    }

    /**
     * Get the portion of the string before the first occurrence of the given value.
     *
     * @param string $subject
     * @param string $search
     *
     * @return string
     */
    public static function before($subject, $search)
    {
        if ('' === $search) {
            return $subject;
        }

        $result = strstr($subject, (string) $search, true);
        return (false === $result) ? $subject : $result;
    }

    /**
     * Get the portion of the string after the first occurrence of the given value.
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
     * Transform string into camel-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function camel($value)
    {
        if (! isset(static::$camel[$value])) {
            static::$camel[$value] = lcfirst(static::studly($value));
        }

        return static::$camel[$value];
    }

    /**
     * Transform string into studly-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function studly($value)
    {
        $key = $value;

        if (! isset(static::$studly[$key])) {
            static::$studly[$key] = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
        }

        return static::$studly[$key];
    }

    /**
     * Transform string into kebab-case.
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
     * Transform string into snake-case.
     *
     * @param string $value
     * @param string $delimiter
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

        return static::$snake[$key][$delimiter] = $value;
    }

    /**
     * Check if the given string contains the given substring.
     *
     * @param string       $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        $haystack = (string) $haystack;
        $needles = (array) $needles;

        foreach ($needles as $needle) {
            if ('' !== $needle && false !== mb_strpos($haystack, $needle, 0, 'UTF-8')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the given string contains all values in the array.
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
     * Starts the given string with the given value.
     *
     * @param string $value
     * @param string $prefix
     *
     * @return string
     */
    public static function start($value, $prefix)
    {
        return $prefix.preg_replace('/^(?:'.preg_quote((string) $prefix, '/').')+/u', '', (string) $value);
    }

    /**
     * Check if the given string starts with the given value.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function starts_with($haystack, $needle)
    {
        $needle = (string) $needle;
        return ('' !== $needle && 0 === strncmp((string) $haystack, $needle, mb_strlen($needle, '8bit')));
    }

    /**
     * Check if the given string ends with the given value.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function ends_with($haystack, $needle)
    {
        $needle = (string) $needle;
        $haystack = (string) $haystack;
        return ('' !== $needle && ($needle === substr($haystack, -mb_strlen($needle, '8bit'))));
    }

    /**
     * Ends the given string with the given value.
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
     * Parses a callback string into an array.
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
     * Converts integer to char according to ctype rules.
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

    /**
     * Get a fluent instance of the given string.
     *
     * @param string $value
     *
     * @return Stringable
     */
    public static function of($value)
    {
        return new Stringable($value);
    }

    /**
     * Mask part of a string: Str::mask('budi@example.com', '*', 2, 8) gives 'bu********le.com'.
     * A negative index counts from the end.
     *
     * @param string   $string
     * @param string   $character
     * @param int      $index
     * @param int|null $length
     *
     * @return string
     */
    public static function mask($string, $character, $index, $length = null)
    {
        $string = (string) $string;

        if ('' === (string) $character) {
            return $string;
        }

        $total = mb_strlen($string, 'UTF-8');
        $start = ($index < 0) ? max(0, $total + (int) $index) : min($total, (int) $index);
        $count = is_null($length) ? $total - $start : max(0, min((int) $length, $total - $start));

        if (0 === $count) {
            return $string;
        }

        return mb_substr($string, 0, $start, 'UTF-8')
            .str_repeat(mb_substr((string) $character, 0, 1, 'UTF-8'), $count)
            .mb_substr($string, $start + $count, $total, 'UTF-8');
    }

    /**
     * Trim a string and collapse every run of whitespace inside it to one space.
     *
     * @param string $value
     *
     * @return string
     */
    public static function squish($value)
    {
        $value = preg_replace('~^[\s\x{FEFF}\x{3164}\x{1160}]+|[\s\x{FEFF}\x{3164}\x{1160}]+$~u', '', (string) $value);
        return preg_replace('~[\s\x{3164}\x{1160}]+~u', ' ', $value);
    }

    /**
     * Turn a camel, snake, kebab or spaced string into title cased words:
     * 'user_profile-pictureUploaded' gives 'User Profile Picture Uploaded'.
     *
     * @param string $value
     *
     * @return string
     */
    public static function headline($value)
    {
        $value = preg_replace('/(?<=\p{Ll}|\p{N})(?=\p{Lu})/u', ' ', (string) $value);
        $words = preg_split('/[\s_\-]+/u', $value, -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', array_map(function ($word) {
            return mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
        }, $words));
    }

    /**
     * Get the text around the first occurrence of a phrase, NULL when it is absent.
     * Options: 'radius' (characters on each side, 100) and 'omission' ('...').
     *
     * @param string $text
     * @param string $phrase
     * @param array  $options
     *
     * @return string|null
     */
    public static function excerpt($text, $phrase = '', array $options = [])
    {
        $text = (string) $text;
        $radius = isset($options['radius']) ? max(0, (int) $options['radius']) : 100;
        $omission = isset($options['omission']) ? (string) $options['omission'] : '...';
        $position = ('' === (string) $phrase) ? 0 : mb_stripos($text, (string) $phrase, 0, 'UTF-8');

        if (false === $position) {
            return null;
        }

        $total = mb_strlen($text, 'UTF-8');
        $start = max(0, $position - $radius);
        $end = min($total, $position + mb_strlen((string) $phrase, 'UTF-8') + $radius);

        return (($start > 0) ? $omission : '')
            .trim(mb_substr($text, $start, $end - $start, 'UTF-8'))
            .(($end < $total) ? $omission : '');
    }

    /**
     * Get the part of a string after the last occurrence of a value.
     *
     * @param string $subject
     * @param string $search
     *
     * @return string
     */
    public static function after_last($subject, $search)
    {
        $subject = (string) $subject;
        $position = ('' === (string) $search) ? false : strrpos($subject, (string) $search);

        return (false === $position) ? $subject : (string) substr($subject, $position + strlen((string) $search));
    }

    /**
     * Get the part of a string before the last occurrence of a value.
     *
     * @param string $subject
     * @param string $search
     *
     * @return string
     */
    public static function before_last($subject, $search)
    {
        $subject = (string) $subject;
        $position = ('' === (string) $search) ? false : strrpos($subject, (string) $search);

        return (false === $position) ? $subject : (string) substr($subject, 0, $position);
    }

    /**
     * Get the part of a string between the first $from and the last $to.
     *
     * @param string $subject
     * @param string $from
     * @param string $to
     *
     * @return string
     */
    public static function between($subject, $from, $to)
    {
        if ('' === (string) $from || '' === (string) $to) {
            return (string) $subject;
        }

        return static::before_last(static::after($subject, $from), $to);
    }

    /**
     * Get the part of a string between the first $from and the first $to after it.
     *
     * @param string $subject
     * @param string $from
     * @param string $to
     *
     * @return string
     */
    public static function between_first($subject, $from, $to)
    {
        if ('' === (string) $from || '' === (string) $to) {
            return (string) $subject;
        }

        return static::before(static::after($subject, $from), $to);
    }

    /**
     * Wrap a string with a value, or with two.
     *
     * @param string      $value
     * @param string      $before
     * @param string|null $after
     *
     * @return string
     */
    public static function wrap($value, $before, $after = null)
    {
        return $before.$value.(is_null($after) ? $before : $after);
    }

    /**
     * Remove a wrapping value from both ends, where it is present.
     *
     * @param string      $value
     * @param string      $before
     * @param string|null $after
     *
     * @return string
     */
    public static function unwrap($value, $before, $after = null)
    {
        $value = (string) $value;
        $after = is_null($after) ? $before : $after;

        if ('' !== (string) $before && 0 === strpos($value, (string) $before)) {
            $value = (string) substr($value, strlen((string) $before));
        }

        if ('' !== (string) $after && strlen($value) >= strlen((string) $after)
            && substr($value, -strlen((string) $after)) === (string) $after) {
            $value = (string) substr($value, 0, -strlen((string) $after));
        }

        return $value;
    }

    /**
     * Pad both sides of a string to a length, counted in characters.
     *
     * @param string $value
     * @param int    $length
     * @param string $pad
     *
     * @return string
     */
    public static function pad_both($value, $length, $pad = ' ')
    {
        $short = max(0, (int) $length - mb_strlen((string) $value, 'UTF-8'));
        return static::padding($pad, (int) floor($short / 2)).$value.static::padding($pad, (int) ceil($short / 2));
    }

    /**
     * Pad the left side of a string to a length, counted in characters.
     *
     * @param string $value
     * @param int    $length
     * @param string $pad
     *
     * @return string
     */
    public static function pad_left($value, $length, $pad = ' ')
    {
        return static::padding($pad, max(0, (int) $length - mb_strlen((string) $value, 'UTF-8'))).$value;
    }

    /**
     * Pad the right side of a string to a length, counted in characters.
     *
     * @param string $value
     * @param int    $length
     * @param string $pad
     *
     * @return string
     */
    public static function pad_right($value, $length, $pad = ' ')
    {
        return $value.static::padding($pad, max(0, (int) $length - mb_strlen((string) $value, 'UTF-8')));
    }

    /**
     * Repeat a padding string to exactly some characters.
     *
     * @param string $pad
     * @param int    $count
     *
     * @return string
     */
    protected static function padding($pad, $count)
    {
        if ($count < 1 || '' === (string) $pad) {
            return '';
        }

        return mb_substr(str_repeat((string) $pad, $count), 0, $count, 'UTF-8');
    }

    /**
     * Check whether a string is a URL, optionally of the given schemes only.
     *
     * @param mixed $value
     * @param array $protocols
     *
     * @return bool
     */
    public static function is_url($value, array $protocols = [])
    {
        if (! is_string($value) || false === filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        return 0 === count($protocols)
            || in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), array_map('strtolower', $protocols), true);
    }

    /**
     * Check whether a string is valid JSON.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function is_json($value)
    {
        if (! is_string($value) || '' === trim($value)) {
            return false;
        }

        json_decode($value);

        return JSON_ERROR_NONE === json_last_error();
    }

    /**
     * Check whether a string holds 7-bit ASCII characters only.
     *
     * @param string $value
     *
     * @return bool
     */
    public static function is_ascii($value)
    {
        return ! preg_match('/[^\x00-\x7F]/', (string) $value);
    }

    /**
     * Check whether a string is a UUID.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function is_uuid($value)
    {
        return is_string($value) && 1 === preg_match('/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/iD', $value);
    }

    /**
     * Replace several values at once: Str::swap(['Tacos' => 'Burritos'], $text).
     *
     * @param array  $map
     * @param string $subject
     *
     * @return string
     */
    public static function swap(array $map, $subject)
    {
        return strtr((string) $subject, $map);
    }

    /**
     * Take the first characters of a string, or the last ones with a negative limit.
     *
     * @param string $string
     * @param int    $limit
     *
     * @return string
     */
    public static function take($string, $limit)
    {
        $string = (string) $string;

        return ($limit < 0)
            ? mb_substr($string, (int) $limit, mb_strlen($string, 'UTF-8'), 'UTF-8')
            : mb_substr($string, 0, (int) $limit, 'UTF-8');
    }

    /**
     * Reverse a string, character by character.
     *
     * @param string $value
     *
     * @return string
     */
    public static function reverse($value)
    {
        return implode('', array_reverse(preg_split('//u', (string) $value, -1, PREG_SPLIT_NO_EMPTY)));
    }

    /**
     * Get the character position of a value in a string, or FALSE.
     *
     * @param string $haystack
     * @param string $needle
     * @param int    $offset
     *
     * @return int|bool
     */
    public static function position($haystack, $needle, $offset = 0)
    {
        return ('' === (string) $needle) ? false : mb_strpos((string) $haystack, (string) $needle, (int) $offset, 'UTF-8');
    }

    /**
     * Collapse repeated runs of a character to one: 'a  b' gives 'a b'.
     *
     * @param string $string
     * @param string $character
     *
     * @return string
     */
    public static function deduplicate($string, $character = ' ')
    {
        return preg_replace('/(?:'.preg_quote((string) $character, '/').')+/u', (string) $character, (string) $string);
    }

    /**
     * Split a string at its upper case letters: 'FooBar' gives ['Foo', 'Bar'].
     *
     * @param string $string
     *
     * @return array
     */
    public static function ucsplit($string)
    {
        return preg_split('/(?=\p{Lu})/u', (string) $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Registers a new method.
     *
     * @param string   $name
     * @param \Closure $handler
     *
     * @return mixed
     */
    public static function macro($name, \Closure $handler)
    {
        if (method_exists('\System\Str', $name)) {
            throw new \Exception(
                sprintf('Overriding framework method with macro is unsupported: Str::%s()', $name)
            );
        }

        if (method_exists('\System\Stringable', $name)) {
            throw new \Exception(
                sprintf('Overriding framework method with macro is unsupported: Stringable::%s()', $name)
            );
        }

        static::$macros[$name] = $handler;
    }

    /**
     * Handles statically called methods.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        if (! array_key_exists($method, static::$macros)) {
            throw new \BadMethodCallException(sprintf('Method does not exist: %s', $method));
        }

        return call_user_func_array(static::$macros[$method], $parameters);
    }
}
