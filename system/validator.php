<?php

namespace System;

defined('DS') or exit('No direct access.');

use System\Database\Connection;

class Validator
{
    /**
     * Contains the data being validated.
     *
     * @var array
     */
    public $attributes;

    /**
     * Contains the list of error messages resulting from the validation process.
     *
     * @var Messages
     */
    public $errors;

    /**
     * Contains the list of validation rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Contains the list of validation error messages.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Contains the database connection for validating data against the database.
     *
     * @var Connection
     */
    protected $db;

    /**
     * Package where validation is executed.
     *
     * @var string
     */
    protected $package = DEFAULT_PACKAGE;

    /**
     * Language from which error messages should be retrieved.
     *
     * @var string
     */
    protected $language;

    /**
     * List of validation rules that are related to size.
     *
     * @var array
     */
    protected $sizes = ['size', 'between', 'min', 'max'];

    /**
     * List of validation rules that are related to numeric values.
     *
     * @var array
     */
    protected $numerics = ['numeric', 'integer'];

    /**
     * Contains the list of custom validators registered by the user.
     *
     * @var array
     */
    protected static $validators = [];

    /**
     * Constructor.
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
     * Create a new validator instance.
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
     * Register a custom validator.
     *
     * @param string   $name
     * @param \Closure $validator
     */
    public static function register($name, $validator)
    {
        static::$validators[$name] = $validator;
    }

    /**
     * Check if the validation passes (alias).
     *
     * @return bool
     */
    public function passes()
    {
        return $this->valid();
    }

    /**
     * Check if the validation fails (alias).
     *
     * @return bool
     */
    public function fails()
    {
        return $this->invalid();
    }

    /**
     * Check if the validation fails.
     *
     * @return bool
     */
    public function invalid()
    {
        return !$this->valid();
    }

    /**
     * Check if the validation passes.
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
     * Evaluate attribute against a validation rule.
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
     * Determine if the attribute can be validated.
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
     * Determine if the given rule implies a required attribute.
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
     * Add an error message to the validation errors.
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
     * Validate that the required attribute is present in the attribute array.
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
     * Validate that the required attribute is present only if
     * any of the other given fields are present.
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
     * Validate that attribute has been confirmed.
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
     * Validate that attribute has been accepted.
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
     * Validate that attribute is a boolean.
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
     * Validate that attribute is the same as another attribute.
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
     * Validate that attribute is different from another attribute.
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
     * Validate that attribute is a number.
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
     * Validate that attribute is an integer.
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
     * Validate the size of the attribute.
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
     * Validate that the size of the attribute is between a range of values.
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
     * Validate that the size of the attribute is greater than the minimum value.
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
     * Validate that the size of the attribute is less than the maximum value.
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
     * Validate that the size of the attribute is greater than the minimum value.
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
     * Validate that the size of the attribute is greater than or equal to the minimum value.
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
     * Validate that the size of the attribute is less than the maximum value.
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
     * Validate that the size of the attribute is less than or equal to the maximum value.
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
     * Validate that the attribute must have digits with exact length.
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
     * Validate that the attribute must have digits with length between minimum and maximum.
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
     * Validate that the attribute must be a string.
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
     * Validate that the attribute must be a valid JSON string.
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
     * Validate that the attribute must be a valid timezone.
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
     * Validate that the attribute must be a valid IPv4 address.
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
     * Validate that the attribute must be a valid IPv6 address.
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
     * Validate that the attribute must not match the given regex pattern.
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
     * Validate that the attribute must be present in the input, even if empty.
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
     * Validate that the attribute must be present in the input and not empty.
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
     * Validate that the attribute must be a valid file.
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
     * Validate that the attribute must have a given MIME type.
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
     * Validate that the attribute must have a valid image dimensions.
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
     * Validate that the attribute must not have any duplicate array elements.
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
     * Validate that the attribute ends with a given value.
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
     * Validate that the attribute starts with a given value.
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
     * Validate that the attribute must be in the given array.
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
     * Validate that the attribute must have an equal date value.
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
     * Validate that the attribute must be present if the second field is equal to any value.
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
     * Validate that the attribute is required unless the condition is met.
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
     * Validate that the attribute is required with all other attributes.
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
     * Validate that the attribute is required without any other attributes.
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
     * Validate that the attribute is required without all other attributes.
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
     * Validate that the attribute is nullable (not required if null).
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return bool
     */
    protected function validate_nullable($attribute, $value)
    {
        return $value === null || $this->validate_required($attribute, $value);
    }

    /**
     * Get the size of the attribute.
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
     * Validate that the attribute is in the array.
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
     * Validate that the attribute is not in the array.
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
     * Validate the uniqueness of the attribute value in the database table.
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
     * Validate that the value of the attribute exists in the database table.
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
     * Validate that the attribute is a valid IP address.
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
     * Validate that the attribute is a valid email address.
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
     * Validate that the attribute is a valid URL.
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
     * Validate that the attribute is a valid UUID (v4).
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
     * Validate that the attribute is an active URL.
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

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout in 5 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (PHP_VERSION_ID < 80000) {
            /** @disregard */
            curl_close($ch);
        }

        return ($code >= 200 && $code < 400);
    }

    /**
     * Validate that the attribute is an image.
     * Valid mime types are: jpeg, png, gif, bmp, svg and webp.
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
     * Validate that the attribute is an alphabetic string.
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
     * Validate that the attribute is an alphanumeric string.
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
     * Validate that the attribute is an alphanumeric string with dashes and underscores.
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
     * Validate that the attribute matches the given regex pattern.
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
     * Validate that the attribute passes the given regex pattern.
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
     * Validate that the attribute has a valid MIME type.
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
     * Validate that the attribute is an array.
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
     * Validate that the attribute is an array and has the same number of elements.
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
     * Validate that the attribute is an array and has at least the number of given elements.
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
     * Validate that the attribute is an array and has at most the number of given elements.
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
     * Validate that the attribute is an array and has numbers of elements between the given range.
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
     * Validate that the attribute's date is before the given date.
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
     * Validate that the attribute's date is before or equal to the given date.
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
     * Validate that the attribute is a valid date.
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
     * Validate that the date is after the given date.
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
     * Validate that the date is matches the given format.
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
     * Get the proper error messages.
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
     * Replace all placeholders in the error message.
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
     * Replace placeholders for the 'required_with' rule.
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
     * Replace placeholders for the 'between' rule.
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
     * Replace placeholders for the 'size' rule.
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
     * Replace placeholders for the 'min' rule.
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
     * Replace placeholders for the 'max' rule.
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
     * Replace placeholders for the 'in' rule.
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
     * Replace placeholders for the 'not_in' rule.
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
     * Replace placeholders for the 'mimes' rule.
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
     * Replace placeholders for the 'same' rule.
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
     * Replace placeholders for the 'different' rule.
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
     * Replace placeholders for the 'before' rule.
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
     * Replace placeholders for the 'before_or_equals' rule.
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
     * Replace placeholders for the 'after' rule.
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
     * Replace placeholders for the 'count' rule.
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
     * Replace placeholders for the 'countmin' rule.
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
     * Replace placeholders for the 'countmax' rule.
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
     * Replace placeholders for the 'countbetween' rule.
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
     * Replace placeholders for the 'gt' rule.
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
     * Replace placeholders for the 'gte' rule.
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
     * Replace placeholders for the 'lt' rule.
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
     * Replace placeholders for the 'lte' rule.
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
     * Replace placeholders for the 'digits' rule.
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
     * Replace placeholders for the 'digits_between' rule.
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
     * Replace placeholders for the 'mimetypes' rule.
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
     * Replace placeholders for the 'ends_with' rule.
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
     * Replace placeholders for the 'starts_with' rule.
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
     * Replace placeholders for the 'in_array' rule.
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
     * Replace placeholders for the 'date_equals' rule.
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
     * Replace placeholders for the 'required_if' rule.
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
     * Replace placeholders for the 'required_unless' rule.
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
     * Replace placeholders for the 'required_with_all' rule.
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
     * Replace placeholders for the 'required_without' rule.
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
     * Replace placeholders for the 'required_without_all' rule.
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
     * Get the name of the attribute from the given attribute.
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
     * Determine if the attribute has a rule set for it.
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
     * Get the name and parameter rule from a rule.
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
     * Set the package that should run the validator.
     * This is to determine which validation language will be used.
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
     * Set the language from which error messages should be retrieved.
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
     * Set the database connection that should be used by the validator.
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
     * Get the database connection that should be used by the validator.
     *
     * @return Connection
     */
    protected function db()
    {
        return $this->db = is_null($this->db) ? Database::connection() : $this->db;
    }

    /**
     * Handle custom validator calls.
     */
    public function __call($method, $parameters)
    {
        if (isset(static::$validators[$method = substr($method, 9)])) {
            return call_user_func_array(static::$validators[$method], $parameters);
        }

        throw new \Exception(sprintf('Method does not exists: %s', $method));
    }
}
