<?php

namespace System;

defined('DS') or exit('No direct access.');

class Str
{
    /**
     * Berisi method tambahan dari user.
     *
     * @var array
     */
    public static $macros = [];

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
     * Bucket untuk ULID.
     *
     * @var array
     */
    private static $ulids = ['time' => 0, 'chars' => []];

    /**
     * Hitung panjang string.
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
        return mb_substr((string) $string, $start, $length, 'UTF-8');
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
        return static::upper(static::substr($string, 0, 1)) . static::substr($string, 1);
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
        return mb_strtolower((string) $value, 'UTF-8');
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
        return mb_strtoupper((string) $value, 'UTF-8');
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

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * Trim whitespace ASCII dan multi-byte whitespaces (cth. Whitespace milik Ms. Word).
     *
     * @param string $value
     *
     * @return string
     */
    public static function trim($value)
    {
        return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $value);
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
        preg_match('/^\s*+(?:\S++\s*+){1,' . ((int) $words) . '}/u', $value, $matches);

        if (!isset($matches[0]) || static::length($value) === static::length($matches[0])) {
            return $value;
        }

        return rtrim($matches[0]) . $end;
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
        $string = (string) $string;

        if (empty(static::$strings)) {
            static::$strings = Config::get('strings');
        }

        if (in_array(mb_strtolower($string, 'UTF-8'), static::$strings['uncountable'])) {
            return $string;
        }

        foreach (static::$strings['irregular'] as $result => $pattern) {
            $pattern = '/' . $pattern . '$/i';

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
        $string = (string) $string;

        if (empty(static::$strings)) {
            static::$strings = Config::get('strings');
        }

        if (in_array(mb_strtolower($string, 'UTF-8'), static::$strings['uncountable'])) {
            return $string;
        }

        foreach (static::$strings['irregular'] as $pattern => $result) {
            $pattern = '/' . $pattern . '$/i';

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
        return implode('', $parts) . static::plural(array_pop($parts), $count);
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
        $value = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $value);
        $value = str_replace('@', $separator . 'at' . $separator, $value);
        $value = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', static::lower($value));
        $value = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $value);

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

        while (($length2 = mb_strlen($string, '8bit')) < $length) {
            $size = $length - $length2;
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode(static::bytes($size))), 0, $size);
        }

        return $string;
    }

    /**
     * Hasilkan string password acak.
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

        if ($max < 1 || $length < 1) {
            return $result;
        }

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[static::integers(0, $max - 1)];
        }

        return $result;
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
        if (!is_int($length)) {
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
            if ($length === mb_strlen((string) $bytes, '8bit')) {
                return $bytes;
            }
        }

        // Openssl gagal, coba /dev/urandom (unix)
        if ($unix) {
            $urandom = true;
            $basedir = ini_get('open_basedir');

            if (!empty($basedir)) {
                $paths = explode(PATH_SEPARATOR, strtolower((string) $basedir));
                $urandom = ([] !== array_intersect(['/dev', '/dev/', '/dev/urandom'], $paths));
                unset($paths);
            }

            if ($urandom && @is_readable('/dev/urandom')) {
                $file = fopen('/dev/urandom', 'r');
                $read = 0;
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

        // /dev/urandom juga masih saja gagal, coba CAPICOM (windows)
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

        $attempts = $bits = $bytes = $mask = $shift = 0;
        $range = $max - $min;

        if (!is_int($range)) {
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
        } while (!is_int($val) || $val > $max || $val < $min);

        return (int) $val;
    }

    /**
     * Buat string UUID (versi 4).
     * (Universally Unique Identifier versi 4).
     *
     * @return string
     */
    public static function uuid()
    {
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(static::bytes(16)), 4));
    }

    /**
     * Buat string ULID.
     * (Universally Unique Lexicographically Sortable Identifier).
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
            $time = $characters[$mod] . $time;
            $milliseconds = ($milliseconds - $mod) / 32;
        }

        if (!$duplicate) {
            for ($i = 0; $i < 16; $i++) {
                static::$ulids['chars'][$i] = static::integers(0, 31);
            }
        } else {
            for ($i = 15; $i >= 0 && static::$ulids['chars'][$i] === 31; $i--) {
                static::$ulids['chars'][$i] = 0;
            }

            static::$ulids['chars'][$i]++;
        }

        for ($i = 0; $i < 16; $i++) {
            $random .= $characters[static::$ulids['chars'][$i]];
        }

        return $lowercase ? strtolower($time . $random) : $time . $random;
    }

    /**
     * Buat string dummy lorem ipsum.
     *
     * @param int  $count
     * @param int  $max
     * @param bool $standard
     */
    public static function lorem($count = 1, $max = 20, $standard = true)
    {
        $result = '';

        if ($standard) {
            $result = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, ' .
                'sed do eiusmod tempor incididunt ut labore et dolore magna ' .
                'aliqua.';
        }

        $pool = explode(
            ' ',
            'a ab ad accusamus adipisci alias aliquam amet animi aperiam ' .
                'architecto asperiores aspernatur assumenda at atque aut beatae ' .
                'blanditiis cillum commodi consequatur corporis corrupti culpa ' .
                'cum cupiditate debitis delectus deleniti deserunt dicta ' .
                'dignissimos distinctio dolor ducimus duis ea eaque earum eius ' .
                'eligendi enim eos error esse est eum eveniet ex excepteur ' .
                'exercitationem expedita explicabo facere facilis fugiat harum ' .
                'hic id illum impedit in incidunt ipsa iste itaque iure iusto ' .
                'laborum laudantium libero magnam maiores maxime minim minus ' .
                'modi molestiae mollitia nam natus necessitatibus nemo neque ' .
                'nesciunt nihil nisi nobis non nostrum nulla numquam occaecati ' .
                'odio officia omnis optio pariatur perferendis perspiciatis ' .
                'placeat porro possimus praesentium proident quae quia quibus ' .
                'quo ratione recusandae reiciendis rem repellat reprehenderit ' .
                'repudiandae rerum saepe sapiente sequi similique sint soluta ' .
                'suscipit tempora tenetur totam ut ullam unde vel veniam vero ' .
                'vitae voluptas'
        );

        $max = ($max <= 3) ? 4 : $max;
        $count = ($count < 1) ? 1 : (($count > 2147483646) ? 2147483646 : $count);

        for ($i = 0, $add = ($count - (int) $standard); $i < $add; $i++) {
            shuffle($pool);
            $words = array_slice($pool, 0, mt_rand(3, $max));
            $result .= ((!$standard && $i === 0) ? '' : ' ') . ucfirst(implode(' ', $words)) . '.';
        }

        return $result;
    }

    /**
     * Buat string nano id.
     * Diadaptasi dari: https://github.com/hidehalo/nanoid-php.
     *
     * @param int         $size
     * @param string|null $characters
     *
     * @return string|null
     */
    public static function nanoid($size = 0, $characters = null)
    {
        $size = ($size > 0) ? (int) $size : 21;
        $default = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters = (!is_string($characters) || is_null($characters)) ? $default : $characters;
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

            $pattern = str_replace('\*', '.*', preg_quote($pattern, '/'));

            if (1 === preg_match('/^' . $pattern . '\z/u', $value)) {
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
     * Ganti kemunculan terakhir dari value yang diberikan dalam string.
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
            $result .= ($replacer ? $replacer : $search) . $segment;
        }

        return $result;
    }

    /**
     * Sensor bebrapa huruf dalam kata / kalimat.
     *
     * @param string $string
     * @param string $replacement
     *
     * @return string
     */
    public static function censor($string, $replacement = '*')
    {
        $string = (string) $string;
        $len = strlen($string) - floor(strlen($string) / 2);
        return substr_replace($string, str_repeat($replacement, $len), floor($len / 2), $len);
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
        if ('' === $search) {
            return $subject;
        }

        $result = strstr($subject, (string) $search, true);
        return (false === $result) ? $subject : $result;
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
        if (!isset(static::$camel[$value])) {
            static::$camel[$value] = lcfirst(static::studly($value));
        }

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

        if (!isset(static::$studly[$key])) {
            static::$studly[$key] = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
        }

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
        $lowercased = is_string($chars) && '' !== $chars && !preg_match('/[^a-z]/', $chars);

        if (!$lowercased) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return static::$snake[$key][$delimiter] = $value;
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
        $haystack = (string) $haystack;
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
            if (!static::contains($haystack, $needle)) {
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
        return $prefix . preg_replace('/^(?:' . preg_quote($prefix, '/') . ')+/u', '', $value);
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
        $needle = (string) $needle;
        return ('' !== $needle && 0 === strncmp($haystack, $needle, mb_strlen($needle, '8bit')));
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
        $needle = (string) $needle;
        $haystack = (string) $haystack;
        return ('' !== $needle && ($needle === substr($haystack, -mb_strlen($needle, '8bit'))));
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
        return preg_replace('/(?:' . preg_quote($cap, '/') . ')+$/u', '', $value) . $cap;
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
        if (!is_int($value)) {
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
     * Daftarkan method baru.
     *
     * <code>
     *
     *      // Daftarkan method baru.
     *      Str::macro('reverse', function ($value) {
     *          return strrev($value);
     *      });
     *
     *      // Panggil method baru.
     *      Str::reverse('Hello world!'); // '!dlrow olleH'
     *
     * </code>
     *
     * @param string   $name
     * @param \Closure $handler
     *
     * @return mixed
     */
    public static function macro($name, \Closure $handler)
    {
        if (method_exists('\System\Str', $name)) {
            throw new \Exception(sprintf('Overriding framework method with macro is unsupported: Str::%s()', $name));
        }

        static::$macros[$name] = $handler;
    }

    /**
     * Tangani pemanggilan static method secara dinamis.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $method = array_key_exists($method, static::$macros) ? static::$macros[$method] : ['\System\Str', $method];
        return call_user_func_array($method, $parameters);
    }
}
