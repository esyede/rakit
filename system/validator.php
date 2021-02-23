<?php

namespace System;

defined('DS') or exit('No direct script access.');

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
     * Berisi list pesan validasi.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Berisi koneksi database untuk validasi data terhadap database.
     *
     * @var Database\Connection
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
     * @param mixed $attributes
     * @param array $rules
     * @param array $messages
     */
    public function __construct($attributes, $rules, $messages = [])
    {
        foreach ($rules as $key => &$rule) {
            $rule = is_string($rule) ? explode('|', $rule) : $rule;
        }

        $this->rules = $rules;
        $this->messages = $messages;
        $this->attributes = is_object($attributes) ? get_object_vars($attributes) : $attributes;
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
    public static function make($attributes, $rules, $messages = [])
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
        return ! $this->valid();
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

        if ($validatable && ! $this->{'validate_'.$rule}($attribute, $value, $parameters, $this)) {
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
        return ('required' === $rule || 'accepted' === $rule || 'required_with' === $rule);
    }

    /**
     * Tambahkan sebuah pesan error ke list error validasi.
     *
     * @param string $attribute
     * @param string $rule
     * @param array  $parameters
     */
    protected function error($attribute, $rule, $parameters)
    {
        $target = $this->message($attribute, $rule);
        $message = $this->replace($target, $attribute, $rule, $parameters);

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
        } elseif (is_string($value) && '' === trim($value)) {
            return false;
        } elseif (! is_null(Input::file($attribute))
        && is_array($value)
        && '' === trim($value['tmp_name'])) {
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
    protected function validate_required_with($attribute, $value, $parameters)
    {
        $other = Arr::get($this->attributes, $parameters[0]);

        if ($this->validate_required($parameters[0], $other)) {
            return $this->validate_required($attribute, $value);
        }

        return true;
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
        return $this->validate_same($attribute, $value, [$attribute.'_confirmation']);
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
    protected function validate_same($attribute, $value, $parameters)
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
    protected function validate_different($attribute, $value, $parameters)
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
    protected function validate_size($attribute, $value, $parameters)
    {
        if (! is_numeric($parameters[0])) {
            return false;
        }

        // '==' memang disengaja.
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
    protected function validate_between($attribute, $value, $parameters)
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
    protected function validate_min($attribute, $value, $parameters)
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
    protected function validate_max($attribute, $value, $parameters)
    {
        return $this->size($attribute, $value) <= $parameters[0];
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
        } elseif (array_key_exists($attribute, Input::file())) {
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
    protected function validate_in($attribute, $value, $parameters)
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
    protected function validate_not_in($attribute, $value, $parameters)
    {
        return ! in_array($value, $parameters);
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
    protected function validate_unique($attribute, $value, $parameters)
    {
        if (isset($parameters[1])) {
            $attribute = $parameters[1];
        }

        $query = $this->db()->table($parameters[0])->where($attribute, '=', $value);

        if (isset($parameters[2])) {
            $id = isset($parameters[3]) ? $parameters[3] : 'id';
            $query->where($id, '<>', $parameters[2]);
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
    protected function validate_exists($attribute, $value, $parameters)
    {
        if (isset($parameters[1])) {
            $attribute = $parameters[1];
        }

        $count = is_array($value) ? count($value) : 1;
        $query = $this->db()->table($parameters[0]);

        if (is_array($value)) {
            $query = $query->where_in($attribute, $value);
        } else {
            $query = $query->where($attribute, '=', $value);
        }

        return $query->count() >= $count;
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
        if (! is_string($value)) {
            return false;
        }

        return (bool) preg_match('{^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$}Di', $value);
    }

    /**
     * Validasi bahwa atribut merupakan string ASCII yang valid.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_ascii($attribute, $value)
    {
        try {
            $value = (string) $value;
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        if ('' === $value) {
            return true;
        }

        return ! preg_match('/[^\x09\x10\x13\x0A\x0D\x20-\x7E]/', $value);
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
        if (! is_string($value)) {
            return false;
        }

        if ($url = parse_url(trim($value), PHP_URL_HOST)) {
            try {
                return count(dns_get_record($url, DNS_A | DNS_AAAA)) > 0;
            } catch (\Throwable $e) {
                return false;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
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
        $extensions = ['jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];

        return $this->validate_mimes($attribute, $value, $extensions);
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
        return is_string($value) && preg_match('/^[\pL\pM]+$/u', $value);
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
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN]+$/u', $value) > 0;
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
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN_-]+$/u', $value) > 0;
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
    protected function validate_match($attribute, $value, $parameters)
    {
        return preg_match($parameters[0], $value);
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
    protected function validate_mimes($attribute, $value, $parameters)
    {
        if (! is_array($value) || '' === Arr::get($value, 'tmp_name', '')) {
            return true;
        }

        foreach ($parameters as $extension) {
            if (File::is($extension, $value['tmp_name'])) {
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
    protected function validate_array($attribute, $value)
    {
        return is_array($value);
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
    protected function validate_count($attribute, $value, $parameters)
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
    protected function validate_countmin($attribute, $value, $parameters)
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
    protected function validate_countmax($attribute, $value, $parameters)
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
    protected function validate_countbetween($attribute, $value, $parameters)
    {
        return ($this->validate_array($attribute, $value)
            && count($value) >= $parameters[0]
            && count($value) <= $parameters[1]
        );
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
    protected function validate_before($attribute, $value, $parameters)
    {
        return strtotime($value) < strtotime($parameters[0]);
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
    protected function validate_before_or_equals($attribute, $value, $parameters)
    {
        return strtotime($value) <= strtotime($parameters[0]);
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
    protected function validate_after($attribute, $value, $parameters)
    {
        return strtotime($value) > strtotime($parameters[0]);
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
    protected function validate_date_format($attribute, $value, $parameters)
    {
        return false !== date_create_from_format($parameters[0], $value);
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

        // Contoh: 'username_required', 'peraturan_accepted'
        $custom = $attribute.'_'.$rule;

        if (array_key_exists($custom, $this->messages)) {
            return $this->messages[$custom];
        } elseif (Lang::has($custom = $package.'validation.custom.'.$custom, $this->language)) {
            return Lang::line($custom)->get($this->language);
        } elseif (array_key_exists($rule, $this->messages)) {
            return $this->messages[$rule];
        } elseif (in_array($rule, $this->sizes)) {
            return $this->size_message($package, $attribute, $rule);
        }

        $line = $package.'validation.'.$rule;

        return Lang::line($line)->get($this->language);
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
        if ($this->has_rule($attribute, $this->numerics)) {
            $line = 'numeric';
        } elseif (array_key_exists($attribute, Input::file())) {
            $line = 'file';
        } else {
            $line = 'string';
        }

        return Lang::line($package.'validation.'.$rule.'.'.$line)->get($this->language);
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
    protected function replace($message, $attribute, $rule, $parameters)
    {
        $message = str_replace(':attribute', $this->attribute($attribute), $message);
        $method = 'replace_'.$rule;

        if (method_exists($this, $method)) {
            $message = $this->{$method}($message, $attribute, $rule, $parameters);
        }

        return $message;
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
    protected function replace_required_with($message, $attribute, $rule, $parameters)
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
    protected function replace_between($message, $attribute, $rule, $parameters)
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
    protected function replace_size($message, $attribute, $rule, $parameters)
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
    protected function replace_min($message, $attribute, $rule, $parameters)
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
    protected function replace_max($message, $attribute, $rule, $parameters)
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
    protected function replace_in($message, $attribute, $rule, $parameters)
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
    protected function replace_not_in($message, $attribute, $rule, $parameters)
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
    protected function replace_mimes($message, $attribute, $rule, $parameters)
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
    protected function replace_same($message, $attribute, $rule, $parameters)
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
    protected function replace_different($message, $attribute, $rule, $parameters)
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
    protected function replace_before($message, $attribute, $rule, $parameters)
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
    protected function replace_before_or_equals($message, $attribute, $rule, $parameters)
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
    protected function replace_after($message, $attribute, $rule, $parameters)
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
    protected function replace_count($message, $attribute, $rule, $parameters)
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
    protected function replace_countmin($message, $attribute, $rule, $parameters)
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
    protected function replace_countmax($message, $attribute, $rule, $parameters)
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
    protected function replace_countbetween($message, $attribute, $rule, $parameters)
    {
        return str_replace([':min', ':max'], $parameters, $message);
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
        $line = $package.'validation.attributes.'.$attribute;

        if (Lang::has($line, $this->language)) {
            return Lang::line($line)->get($this->language);
        }

        return str_replace('_', ' ', $attribute);
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
        $parameters = [];

        if (false !== ($colon = strpos($rule, ':'))) {
            $parameters = str_getcsv(substr($rule, $colon + 1));
        }

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
     * @param Database\Connection $connection
     *
     * @return $this
     */
    public function connection(Database\Connection $connection)
    {
        $this->db = $connection;

        return $this;
    }

    /**
     * Ambil object koneksi database.
     *
     * @return Database\Connection
     */
    protected function db()
    {
        if (! is_null($this->db)) {
            return $this->db;
        }

        $this->db = Database::connection();

        return $this->db;
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
