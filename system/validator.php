<?php

namespace System;

defined('DS') or exit('No direct access.');

use System\Database\Connection;

class Validator
{
    /**
     * Berisi array data yang sedang divalidasi.
     *
     * @var array
     */
    public $attributes;

    /**
     * Berisi list pesan error hasil proses validasi.
     *
     * @var Messages
     */
    public $errors;

    /**
     * Berisi list rule validasi.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Berisi list pesan error validasi.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Berisi koneksi database untuk validasi data terhadap database.
     *
     * @var Connection
     */
    protected $db;

    /**
     * Package tempat dimana validasi dijalankan.
     *
     * @var string
     */
    protected $package = DEFAULT_PACKAGE;

    /**
     * Dari bahasa mana pesan-pesan error harus diambil.
     *
     * @var string
     */
    protected $language;

    /**
     * List rule validasi yang berhubungan dengan ukuran.
     *
     * @var array
     */
    protected $sizes = ['size', 'between', 'min', 'max'];

    /**
     * List rule validasi yang berhubungan dengan angka.
     *
     * @var array
     */
    protected $numerics = ['numeric', 'integer'];

    /**
     * Berisi list validator kustom yang didaftarkan oleh user.
     *
     * @var array
     */
    protected static $validators = [];

    /**
     * Buat sebuah instance validator baru.
     *
     * @param array $attributes
     * @param array $rules
     * @param array $messages
     */
    public function __construct(array $attributes, array $rules, array $messages = [])
    {
        foreach ($rules as $key => &$rule) {
            $rule = is_string($rule) ? explode('|', $rule) : $rule;
        }

        $this->rules = $rules;
        $this->messages = $messages;
        $this->attributes = $attributes;
    }

    /**
     * Buat sebuah instance validator baru.
     *
     * @param array $attributes
     * @param array $rules
     * @param array $messages
     *
     * @return Validator
     */
    public static function make(array $attributes, array $rules, array $messages = [])
    {
        return new static($attributes, $rules, $messages);
    }

    /**
     * Daftarkan sebuah validator kustom.
     *
     * @param string   $name
     * @param \Closure $validator
     */
    public static function register($name, $validator)
    {
        static::$validators[$name] = $validator;
    }

    /**
     * Validasi array target menggunakan ruleset yang diberikan.
     *
     * @return bool
     */
    public function passes()
    {
        return $this->valid();
    }

    /**
     * Validasi array target menggunakan ruleset yang diberikan.
     *
     * @return bool
     */
    public function fails()
    {
        return $this->invalid();
    }

    /**
     * Validasi array target menggunakan ruleset yang diberikan.
     *
     * @return bool
     */
    public function invalid()
    {
        return !$this->valid();
    }

    /**
     * Validasi array target menggunakan ruleset yang diberikan.
     *
     * @return bool
     */
    public function valid()
    {
        $this->errors = new Messages();

        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                $this->check($attribute, $rule);
            }
        }

        return 0 === count($this->errors->messages);
    }

    /**
     * Evaluasi atribut terhadap sebuah rule validasi.
     *
     * @param string $attribute
     * @param string $rule
     */
    protected function check($attribute, $rule)
    {
        list($rule, $parameters) = $this->parse($rule);

        $value = Arr::get($this->attributes, $attribute);
        $validatable = $this->validatable($rule, $attribute, $value);

        if ($validatable && !$this->{'validate_' . $rule}($attribute, $value, $parameters, $this)) {
            $this->error($attribute, $rule, $parameters);
        }
    }

    /**
     * Periksa apakah atribut benar-benar bisa divalidasi.
     * Atribut diannpggap bisa divalidasi jika atributnya ada, atau rule
     * yang di periksa harus secara implisit memvalidasi 'required',
     * seperti required yang ada di rule 'accepted'.
     *
     * @param string $rule
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validatable($rule, $attribute, $value)
    {
        if (in_array('nullable', $this->rules[$attribute]) && is_null($value)) {
            return false;
        }

        return $this->validate_required($attribute, $value) || $this->implicit($rule);
    }

    /**
     * Tentukan apakah rule yang diberikan mengimplikasikan bahwa
     * atribut tersebut diperlukan.
     *
     * @param string $rule
     *
     * @return bool
     */
    protected function implicit($rule)
    {
        return in_array($rule, [
            'required',
            'accepted',
            'required_with',
            'present',
            'filled',
            'required_if',
            'required_unless',
            'required_with_all',
            'required_without',
            'required_without_all',
        ]);
    }

    /**
     * Tambahkan sebuah pesan error ke list error validasi.
     *
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     */
    protected function error($attribute, $rule, array $parameters)
    {
        $message = $this->replace($this->message($attribute, $rule), $attribute, $rule, $parameters);
        $this->errors->add($attribute, $message);
    }

    /**
     * Validasi bahwa atribut yang diperlukan ada di array atribut.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_required($attribute, $value)
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value) && '' === trim($value)) {
            return false;
        }

        if (!is_null(Input::file($attribute)) && is_array($value) && '' === trim($value['tmp_name'])) {
            return false;
        }

        return true;
    }

    /**
     * Validasi bahwa suatu atribut ada dalam array atribut, jika atribut lain
     * ada dalam array atribut.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_required_with($attribute, $value, array $parameters)
    {
        return $this->validate_required($parameters[0], Arr::get($this->attributes, $parameters[0]))
            ? $this->validate_required($attribute, $value)
            : true;
    }

    /**
     * Validasi bahwa suatu atribut memiliki atribut konfirmasi yang cocok.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_confirmed($attribute, $value)
    {
        return $this->validate_same($attribute, $value, [$attribute . '_confirmation']);
    }

    /**
     * Validasi bahwa suatu atribut 'diterima'.
     * Rule validasi ini mengimplikasikan bahwa atribut ini 'required'.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_accepted($attribute, $value)
    {
        return $this->validate_required($attribute, $value)
            && in_array($value, ['yes', 'on', '1', 1, true, 'true'], true);
    }

    /**
     * Validasi bahwa suatu atribut berisi boolean.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_boolean($attribute, $value)
    {
        return in_array($value, [true, false, 0, 1, '0', '1'], true);
    }

    /**
     * Validasi bahwa suatu atribut sama dengan atribut lainnya.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_same($attribute, $value, array $parameters)
    {
        return array_key_exists($parameters[0], $this->attributes)
            && ($value === $this->attributes[$parameters[0]]);
    }

    /**
     * Validasi bahwa suatu atribut berbeda dengan atribut lainnya.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_different($attribute, $value, array $parameters)
    {
        return array_key_exists($parameters[0], $this->attributes)
            && ($value !== $this->attributes[$parameters[0]]);
    }

    /**
     * Validasi bahwa suatu atribut adalah angka.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_numeric($attribute, $value)
    {
        return is_numeric($value);
    }

    /**
     * Validasi bahwa suatu atribut adalah bilangan bulat.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_integer($attribute, $value)
    {
        return false !== filter_var($value, FILTER_VALIDATE_INT);
    }

    /**
     * Validasi ukuran atribut.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_size($attribute, $value, array $parameters)
    {
        if (!is_numeric($parameters[0])) {
            return false;
        }

        // '==' memang disengaja untuk loosey comparison.
        return $this->size($attribute, $value) == $parameters[0];
    }

    /**
     * Validasi bahwa ukuran atribut berada diantara seperangkat nilai.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_between($attribute, $value, array $parameters)
    {
        $size = $this->size($attribute, $value);
        return ($size >= $parameters[0] && $size <= $parameters[1]);
    }

    /**
     * Validasi bahwa ukuran atribut lebih besar dari nilai minimumnya.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_min($attribute, $value, array $parameters)
    {
        return $this->size($attribute, $value) >= $parameters[0];
    }

    /**
     * Validasi bahwa ukuran atribut lebih kecil dari nilai maksimumnya.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_max($attribute, $value, array $parameters)
    {
        return $this->size($attribute, $value) <= $parameters[0];
    }

    /**
     * Validasi bahwa atribut lebih besar dari atribut lainnya.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_gt($attribute, $value, array $parameters)
    {
        if (!array_key_exists($parameters[0], $this->attributes)) {
            return false;
        }

        return $value > $this->attributes[$parameters[0]];
    }

    /**
     * Validasi bahwa atribut lebih besar atau sama dengan atribut lainnya.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_gte($attribute, $value, array $parameters)
    {
        if (!array_key_exists($parameters[0], $this->attributes)) {
            return false;
        }

        return $value >= $this->attributes[$parameters[0]];
    }

    /**
     * Validasi bahwa atribut lebih kecil dari atribut lainnya.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_lt($attribute, $value, array $parameters)
    {
        if (!array_key_exists($parameters[0], $this->attributes)) {
            return false;
        }

        return $value < $this->attributes[$parameters[0]];
    }

    /**
     * Validasi bahwa atribut lebih kecil atau sama dengan atribut lainnya.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_lte($attribute, $value, array $parameters)
    {
        if (!array_key_exists($parameters[0], $this->attributes)) {
            return false;
        }

        return $value <= $this->attributes[$parameters[0]];
    }

    /**
     * Validasi bahwa atribut memiliki jumlah digit yang tepat.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_digits($attribute, $value, array $parameters)
    {
        return is_numeric($value) && strlen((string) $value) === (int) $parameters[0];
    }

    /**
     * Validasi bahwa atribut memiliki jumlah digit antara nilai minimum dan maksimum.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_digits_between($attribute, $value, array $parameters)
    {
        $length = strlen((string) $value);
        return is_numeric($value) && $length >= (int) $parameters[0] && $length <= (int) $parameters[1];
    }

    /**
     * Validasi bahwa atribut adalah string.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_string($attribute, $value)
    {
        return is_string($value);
    }

    /**
     * Validasi bahwa atribut adalah JSON yang valid.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_json($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validasi bahwa atribut adalah timezone yang valid.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_timezone($attribute, $value)
    {
        return in_array($value, timezone_identifiers_list());
    }

    /**
     * Validasi bahwa atribut adalah alamat IPv4 yang valid.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_ipv4($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validasi bahwa atribut adalah alamat IPv6 yang valid.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_ipv6($attribute, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * Validasi bahwa atribut tidak cocok dengan pola regex.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_not_regex($attribute, $value, array $parameters)
    {
        try {
            return 1 !== preg_match($parameters[0], $value);
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validasi bahwa atribut ada dalam input, meskipun kosong.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_present($attribute, $value)
    {
        return array_key_exists($attribute, $this->attributes);
    }

    /**
     * Validasi bahwa atribut ada dan tidak kosong jika ada.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_filled($attribute, $value)
    {
        return !empty($value);
    }

    /**
     * Validasi bahwa atribut adalah file yang diupload.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_file($attribute, $value)
    {
        return is_array($value) && isset($value['tmp_name']) && is_uploaded_file($value['tmp_name']);
    }

    /**
     * Validasi bahwa atribut file memiliki MIME type yang ditentukan.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_mimetypes($attribute, $value, array $parameters)
    {
        if (!is_array($value) || '' === Arr::get($value, 'tmp_name', '')) {
            return true;
        }

        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $value['tmp_name']);
        return in_array($mime, $parameters);
    }

    /**
     * Validasi dimensi gambar.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_dimensions($attribute, $value, array $parameters)
    {
        if (!is_array($value) || '' === Arr::get($value, 'tmp_name', '')) {
            return true;
        }

        $image = getimagesize($value['tmp_name']);

        if (!$image) {
            return false;
        }

        $dimensions = [];

        foreach ($parameters as $parameter) {
            list($key, $val) = explode('=', $parameter);
            $dimensions[$key] = $val;
        }

        if (isset($dimensions['width']) && $image[0] != $dimensions['width']) {
            return false;
        }

        if (isset($dimensions['height']) && $image[1] != $dimensions['height']) {
            return false;
        }

        if (isset($dimensions['min_width']) && $image[0] < $dimensions['min_width']) {
            return false;
        }

        if (isset($dimensions['max_width']) && $image[0] > $dimensions['max_width']) {
            return false;
        }

        if (isset($dimensions['min_height']) && $image[1] < $dimensions['min_height']) {
            return false;
        }

        if (isset($dimensions['max_height']) && $image[1] > $dimensions['max_height']) {
            return false;
        }

        return true;
    }

    /**
     * Validasi bahwa elemen array unik.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_distinct($attribute, $value)
    {
        if (!is_array($value)) {
            return true;
        }

        return count($value) === count(array_unique($value));
    }

    /**
     * Validasi bahwa string diakhiri dengan nilai tertentu.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_ends_with($attribute, $value, array $parameters)
    {
        if (!is_string($value)) {
            return false;
        }

        foreach ($parameters as $end) {
            if (substr($value, -strlen($end)) === $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validasi bahwa string dimulai dengan nilai tertentu.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_starts_with($attribute, $value, array $parameters)
    {
        if (!is_string($value)) {
            return false;
        }

        foreach ($parameters as $start) {
            if (strpos($value, $start) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validasi bahwa nilai ada dalam array dari atribut lain.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_in_array($attribute, $value, array $parameters)
    {
        if (!array_key_exists($parameters[0], $this->attributes)) {
            return false;
        }

        $other = $this->attributes[$parameters[0]];
        return is_array($other) && in_array($value, $other);
    }

    /**
     * Validasi bahwa tanggal sama dengan tanggal lain.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_date_equals($attribute, $value, array $parameters)
    {
        try {
            return (new \DateTime($value)) == (new \DateTime($parameters[0]));
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validasi bahwa atribut diperlukan jika kondisi terpenuhi.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_required_if($attribute, $value, array $parameters)
    {
        $other = Arr::get($this->attributes, $parameters[0]);

        if (in_array($other, array_slice($parameters, 1))) {
            return $this->validate_required($attribute, $value);
        }

        return true;
    }

    /**
     * Validasi bahwa atribut diperlukan kecuali kondisi terpenuhi.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_required_unless($attribute, $value, array $parameters)
    {
        $other = Arr::get($this->attributes, $parameters[0]);

        if (!in_array($other, array_slice($parameters, 1))) {
            return $this->validate_required($attribute, $value);
        }

        return true;
    }

    /**
     * Validasi bahwa atribut diperlukan dengan semua atribut lain.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_required_with_all($attribute, $value, array $parameters)
    {
        foreach ($parameters as $param) {
            if (!$this->validate_required($param, Arr::get($this->attributes, $param))) {
                return true;
            }
        }

        return $this->validate_required($attribute, $value);
    }

    /**
     * Validasi bahwa atribut diperlukan tanpa atribut lain.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_required_without($attribute, $value, array $parameters)
    {
        foreach ($parameters as $param) {
            if ($this->validate_required($param, Arr::get($this->attributes, $param))) {
                return true;
            }
        }

        return $this->validate_required($attribute, $value);
    }

    /**
     * Validasi bahwa atribut diperlukan tanpa semua atribut lain.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_required_without_all($attribute, $value, array $parameters)
    {
        foreach ($parameters as $param) {
            if (!$this->validate_required($param, Arr::get($this->attributes, $param))) {
                return true;
            }
        }

        return $this->validate_required($attribute, $value);
    }

    /**
     * Validasi bahwa atribut adalah nullable (tidak wajib divalidasi jika null).
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_nullable($attribute, $value)
    {
        return true;
    }

    /**
     * Ambil ukuran atribut.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function size($attribute, $value)
    {
        if (is_numeric($value) && $this->has_rule($attribute, $this->numerics)) {
            return $this->attributes[$attribute];
        }

        if (array_key_exists($attribute, Input::file())) {
            return $value['size'] / 1024;
        }

        return Str::length(trim($value));
    }

    /**
     * Vaidasi bahwa atribut ada dalam array.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_in($attribute, $value, array $parameters)
    {
        return in_array($value, $parameters);
    }

    /**
     * Vaidasi bahwa atribut tidak ada dalam array.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_not_in($attribute, $value, array $parameters)
    {
        return !in_array($value, $parameters);
    }

    /**
     * Validasi keunikan value atribut pada tabel database yang diberikan.
     * Jika kolom database tidak ditentukan, atribut akan digunakan sebagai nama kolom.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_unique($attribute, $value, array $parameters)
    {
        if (isset($parameters[1])) {
            $attribute = $parameters[1];
        }

        $query = $this->db()->table($parameters[0])->where($attribute, '=', $value);

        if (isset($parameters[2])) {
            $query->where(isset($parameters[3]) ? $parameters[3] : 'id', '<>', $parameters[2]);
        }

        return 0 === (int) $query->count();
    }

    /**
     * Validasi bahwa value atribut ada didalam tabel database.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_exists($attribute, $value, array $parameters)
    {
        $query = $this->db()->table($parameters[0]);
        $attribute = isset($parameters[1]) ? $parameters[1] : $attribute;

        if (is_array($value)) {
            $query->where_in($attribute, $value);
        } else {
            $query->where($attribute, '=', $value);
        }

        return $query->count() >= (is_array($value) ? count($value) : 1);
    }

    /**
     * Validasi bahwa atribut merupakan alamat IP yang valid.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_ip($attribute, $value)
    {
        return false !== filter_var($value, FILTER_VALIDATE_IP);
    }

    /**
     * Validasi bahwa atribut merupakan alamat email yang valid.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_email($attribute, $value)
    {
        return false !== filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validasi bahwa atribut merupakan URL yang valid.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_url($attribute, $value)
    {
        return false !== filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * Validasi bahwa atribut merupakan string UUID (v4) yang valid.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_uuid($attribute, $value)
    {
        try {
            return is_string($value)
                ? (bool) preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/Di', $value)
                : false;
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validasi bahwa atribut merupakan URL yang aktif.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_active_url($attribute, $value)
    {
        if (!is_string($value)) {
            return false;
        }

        $url = trim($value);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Gunakan cURL untuk pengecekan yang lebih efisien daripada dns_get_record
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout 5 detik untuk performa
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        /** @disregard */
        curl_close($ch);

        return $code >= 200 && $code < 400;
    }

    /**
     * Validasi bahwa mime-type sebuah file merupakan mime-type gambar.
     * Mime-type gambar yang valid adalah: jpeg, png, gif, bmp, svg dan webp.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_image($attribute, $value)
    {
        return $this->validate_mimes($attribute, $value, ['jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']);
    }

    /**
     * Validasi bahwa atribut hanya mengandung karakter-karakter alfabet.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_alpha($attribute, $value)
    {
        try {
            return is_string($value) && 1 === preg_match('/^[\pL\pM]+$/u', $value);
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validasi bahwa atribut hanya mengandung karakter-karakter alfabet dan angka.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_alpha_num($attribute, $value)
    {
        try {
            return (is_string($value) || is_numeric($value)) ? (1 === preg_match('/^[\pL\pM\pN]+$/u', $value)) : false;
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validasi bahwa atribut hanya mengandung karakter-karakter
     * alfabet, angka, tanda hubung dan garis bawah.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_alpha_dash($attribute, $value)
    {
        try {
            return (is_string($value) || is_numeric($value)) ? (1 === preg_match('/^[\pL\pM\pN_-]+$/u', $value)) : false;
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validasi bahwa atribut lolos dari pengecekan regex.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_match($attribute, $value, array $parameters)
    {
        try {
            return 1 === preg_match(implode(',', (array) $parameters), $value);
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validasi bahwa atribut lolos dari pengecekan regex.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_regex($attribute, $value, array $parameters)
    {
        try {
            return 1 === preg_match($parameters[0], $value);
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validasi bahwa atribut file upload ada dalam array mime-type yang ditentukan.
     *
     * @param string $attribute
     * @param array  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_mimes($attribute, $value, array $parameters)
    {
        if (!is_array($value) || '' === Arr::get($value, 'tmp_name', '')) {
            return true;
        }

        foreach ($parameters as $extension) {
            if (Storage::is($extension, $value['tmp_name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validasi bahwa atribut merupakan sebuah array.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_array($attribute, $value, array $parameters = [])
    {
        if (!is_array($value)) {
            return false;
        }

        if (empty($attribute)) {
            return true;
        }

        $value = array_diff_key($value, array_fill_keys($parameters, ''));
        return empty($value);
    }

    /**
     * Validasi bahwa atribut merupakan array dengan jumlah elemen
     * yang sama dengan jumlah elemen yang ditentukan.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_count($attribute, $value, array $parameters)
    {
        return ($this->validate_array($attribute, $value) && $parameters[0] === count($value));
    }

    /**
     * Validasi bahwa atribut merupakan array dengan jumlah elemen yang
     * tidak kurang dari jumlah elemen minimum yang ditentukan.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_countmin($attribute, $value, array $parameters)
    {
        return ($this->validate_array($attribute, $value) && count($value) >= $parameters[0]);
    }

    /**
     * Validasi bahwa atribut merupakan array dengan jumlah elemen yang
     * tidak lebih dari jumlah elemen maksimum yang ditentukan.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_countmax($attribute, $value, array $parameters)
    {
        return ($this->validate_array($attribute, $value) && count($value) <= $parameters[0]);
    }

    /**
     * Validasi bahwa atribut merupakan array dengan jumlah elemen yang
     * berada pada rentang elemen minimum dan maksimum yang ditentukan.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_countbetween($attribute, $value, array $parameters)
    {
        return ($this->validate_array($attribute, $value)
            && count($value) >= $parameters[0] && count($value) <= $parameters[1]);
    }

    /**
     * Validasi tanggal ini adalah sebelum tanggal yang ditentukan.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_before($attribute, $value, array $parameters)
    {
        try {
            return (new \DateTime($value)) < (new \DateTime($parameters[0]));
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validasi tanggal ini adalah sebelum atau tepat tanggal yang ditentukan.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_before_or_equals($attribute, $value, array $parameters)
    {
        try {
            return (new \DateTime($value)) <= (new \DateTime($parameters[0]));
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validasi bahwa atribut merupakan sebuah tanggal.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_date($attribute, $value)
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        try {
            if ((!is_string($value) && !is_numeric($value)) || strtotime($value) === false) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        $date = date_parse($value);
        return checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     * Validasi tanggal ini adalah setelah tanggal yang ditentukan.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_after($attribute, $value, array $parameters)
    {
        try {
            return (new \DateTime($value)) > (new \DateTime($parameters[0]));
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validasi format tanggal cocok dengan format yang ditentukan.
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return bool
     */
    protected function validate_date_format($attribute, $value, array $parameters)
    {
        return (is_string($parameters[0]) || is_numeric($parameters[0]))
            && false !== date_create_from_format($parameters[0], $value);
    }



    /**
     * Ambil pesan error yang sesuai untuk sebuah atribut dan rule.
     *
     * @param string $attribute
     * @param string $rule
     *
     * @return string
     */
    protected function message($attribute, $rule)
    {
        $package = Package::prefix($this->package);
        $custom = $attribute . '_' . $rule;

        if (array_key_exists($custom, $this->messages)) {
            return $this->messages[$custom];
        }

        if (Lang::has($custom = $package . 'validation.custom.' . $custom, $this->language)) {
            return Lang::line($custom)->get($this->language);
        }

        if (array_key_exists($rule, $this->messages)) {
            return $this->messages[$rule];
        }

        if (in_array($rule, $this->sizes)) {
            return $this->size_message($package, $attribute, $rule);
        }

        return Lang::line($package . 'validation.' . $rule)->get($this->language);
    }

    /**
     * Get the proper error message for an attribute and size rule.
     *
     * @param string $package
     * @param string $attribute
     * @param string $rule
     *
     * @return string
     */
    protected function size_message($package, $attribute, $rule)
    {
        $line = $this->has_rule($attribute, $this->numerics)
            ? 'numeric'
            : (array_key_exists($attribute, Input::file()) ? 'file' : 'string');

        return Lang::line($package . 'validation.' . $rule . '.' . $line)->get($this->language);
    }

    /**
     * Replace seluruh palceholder di pesan error dengan value aslinya.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace($message, $attribute, $rule, array $parameters)
    {
        $message = str_replace(':attribute', $this->attribute($attribute), $message);
        return method_exists($this, 'replace_' . $rule)
            ? $this->{'replace_' . $rule}($message, $attribute, $rule, $parameters)
            : $message;
    }

    /**
     * Replace seluruh palceholder untuk rule 'required_with'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_required_with($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':field', $this->attribute($parameters[0]), $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'between'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_between($message, $attribute, $rule, array $parameters)
    {
        return str_replace([':min', ':max'], $parameters, $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'size'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_size($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':size', $parameters[0], $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'min'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_min($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':min', $parameters[0], $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'max'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_max($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':max', $parameters[0], $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'in'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_in($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':values', implode(', ', $parameters), $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'not_in'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_not_in($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':values', implode(', ', $parameters), $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'mimes'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_mimes($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':values', implode(', ', $parameters), $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'same'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_same($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':other', $this->attribute($parameters[0]), $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'different'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_different($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':other', $this->attribute($parameters[0]), $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'before'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_before($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':date', $parameters[0], $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'before_or_equals'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_before_or_equals($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':date', $parameters[0], $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'after'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_after($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':date', $parameters[0], $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'count'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_count($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':count', $parameters[0], $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'countmin'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_countmin($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':min', $parameters[0], $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'countmax'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_countmax($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':max', $parameters[0], $message);
    }

    /**
     * Replace seluruh palceholder untuk rule 'countbetween'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_countbetween($message, $attribute, $rule, array $parameters)
    {
        return str_replace([':min', ':max'], $parameters, $message);
    }

    /**
     * Replace placeholder untuk rule 'gt'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_gt($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':value', $this->attribute($parameters[0]), $message);
    }

    /**
     * Replace placeholder untuk rule 'gte'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_gte($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':value', $this->attribute($parameters[0]), $message);
    }

    /**
     * Replace placeholder untuk rule 'lt'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_lt($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':value', $this->attribute($parameters[0]), $message);
    }

    /**
     * Replace placeholder untuk rule 'lte'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_lte($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':value', $this->attribute($parameters[0]), $message);
    }

    /**
     * Replace placeholder untuk rule 'digits'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_digits($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':digits', $parameters[0], $message);
    }

    /**
     * Replace placeholder untuk rule 'digits_between'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_digits_between($message, $attribute, $rule, array $parameters)
    {
        return str_replace([':min', ':max'], $parameters, $message);
    }

    /**
     * Replace placeholder untuk rule 'mimetypes'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_mimetypes($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':values', implode(', ', $parameters), $message);
    }

    /**
     * Replace placeholder untuk rule 'ends_with'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_ends_with($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':values', implode(', ', $parameters), $message);
    }

    /**
     * Replace placeholder untuk rule 'starts_with'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_starts_with($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':values', implode(', ', $parameters), $message);
    }

    /**
     * Replace placeholder untuk rule 'in_array'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_in_array($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':other', $this->attribute($parameters[0]), $message);
    }

    /**
     * Replace placeholder untuk rule 'date_equals'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_date_equals($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':date', $parameters[0], $message);
    }

    /**
     * Replace placeholder untuk rule 'required_if'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_required_if($message, $attribute, $rule, array $parameters)
    {
        $other = $this->attribute($parameters[0]);
        $values = implode(', ', array_slice($parameters, 1));
        return str_replace([':other', ':value'], [$other, $values], $message);
    }

    /**
     * Replace placeholder untuk rule 'required_unless'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_required_unless($message, $attribute, $rule, array $parameters)
    {
        $other = $this->attribute($parameters[0]);
        $values = implode(', ', array_slice($parameters, 1));
        return str_replace([':other', ':value'], [$other, $values], $message);
    }

    /**
     * Replace placeholder untuk rule 'required_with_all'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_required_with_all($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':values', implode(', ', array_map([$this, 'attribute'], $parameters)), $message);
    }

    /**
     * Replace placeholder untuk rule 'required_without'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_required_without($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':values', implode(', ', array_map([$this, 'attribute'], $parameters)), $message);
    }

    /**
     * Replace placeholder untuk rule 'required_without_all'.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     *
     * @return string
     */
    protected function replace_required_without_all($message, $attribute, $rule, array $parameters)
    {
        return str_replace(':values', implode(', ', array_map([$this, 'attribute'], $parameters)), $message);
    }

    /**
     * Ambil nama atribut dari atribut yang diberikan.
     *
     * @param string $attribute
     *
     * @return string
     */
    protected function attribute($attribute)
    {
        $package = Package::prefix($this->package);
        $line = $package . 'validation.attributes.' . $attribute;

        return Lang::has($line, $this->language)
            ? Lang::line($line)->get($this->language)
            : str_replace('_', ' ', $attribute);
    }

    /**
     * Tentukan apakah atribut memiliki rulw yang ditetapkan untuknya.
     *
     * @param string $attribute
     * @param array  $rules
     *
     * @return bool
     */
    protected function has_rule($attribute, $rules)
    {
        if (!isset($this->rules[$attribute])) {
            return false;
        }

        foreach ($this->rules[$attribute] as $rule) {
            list($rule, $parameters) = $this->parse($rule);

            if (in_array($rule, $rules)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ambil nama dan parameter rule dari sebuah rule.
     *
     * @param string $rule
     *
     * @return array
     */
    protected function parse($rule)
    {
        $rule = (string) $rule;
        $parameters = (false !== ($colon = strpos($rule, ':'))) ? str_getcsv(substr($rule, $colon + 1)) : [];
        return [is_numeric($colon) ? substr($rule, 0, $colon) : $rule, $parameters];
    }

    /**
     * Set paket mana yang harus menjalankan validator.
     * Ini untuk menentukan validation language mana yang akan digunakan.
     *
     * @param string $package
     *
     * @return $this
     */
    public function package($package)
    {
        $this->package = $package;
        return $this;
    }

    /**
     * Set dari bahasa mana pesan-pesan error harus diambil.
     *
     * @param string $language
     *
     * @return $this
     */
    public function speaks($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Set koneksi database mana yang harus digunakan oleh validator.
     *
     * @param Connection $connection
     *
     * @return $this
     */
    public function connection(Connection $connection)
    {
        $this->db = $connection;
        return $this;
    }

    /**
     * Ambil object koneksi database.
     *
     * @return Connection
     */
    protected function db()
    {
        return $this->db = is_null($this->db) ? Database::connection() : $this->db;
    }

    /**
     * Tangani pemanggilan custom validator.
     */
    public function __call($method, $parameters)
    {
        if (isset(static::$validators[$method = substr($method, 9)])) {
            return call_user_func_array(static::$validators[$method], $parameters);
        }

        throw new \Exception(sprintf('Method does not exists: %s', $method));
    }
}
