<?php

namespace System\Foundation\Faker;

defined('DS') or exit('No direct access.');

class Unique
{
    /** @var \System\Foundation\Faker\Generator */
    protected $generator;

    /** @var int */
    protected $max_retries;

    /** @var array */
    protected $uniques = [];

    /**
     * Create a new Unique instance.
     *
     * @param \System\Foundation\Faker\Generator $generator
     * @param int                                $max_retries
     */
    public function __construct($generator, $max_retries = 10000)
    {
        $this->generator = $generator;
        $this->max_retries = $max_retries;
    }

    /**
     * Reset the unique values for a given formatter or all formatters.
     *
     * @param string|null $name
     *
     * @return void
     */
    public function reset($name = null)
    {
        if (is_null($name)) {
            $this->uniques = [];
            return;
        }

        unset($this->uniques[$name]);
    }

    /**
     * Set the maximum number of retries.
     *
     * @param int $max
     *
     * @return void
     */
    public function setMaxRetries($max)
    {
        $this->max_retries = (int) $max;
    }

    /**
     * Get the maximum number of retries.
     *
     * @return int
     */
    public function getMaxRetries()
    {
        return $this->max_retries;
    }

    /**
     * Magic getter to get unique values.
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function __get($attribute)
    {
        return $this->__call($attribute, []);
    }

    /**
     * Magic call to get unique values.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (! isset($this->uniques[$name])) {
            $this->uniques[$name] = [];
        }

        $retry = 0;

        do {
            $result = call_user_func_array([$this->generator, $name], $arguments);
            ++$retry;

            if ($retry > $this->max_retries) {
                throw new \OverflowException(sprintf(
                    'Maximum retries of %s reached without finding a unique value.',
                    $this->max_retries
                ));
            }

            $key = $this->makeKey($result);
        } while (array_key_exists($key, $this->uniques[$name]));

        $this->uniques[$name][$key] = null;
        return $result;
    }

    /**
     * Create a key for a value.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function makeKey($value)
    {
        if (is_null($value)) {
            return 'null:';
        }

        if (is_scalar($value)) {
            return gettype($value).':'.(string) $value;
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            $json = json_encode($this->canonicalizeArray($value));

            if (false !== $json) {
                return 'json:'.$json;
            }
        }

        return 'ser:'.serialize($value);
    }

    /**
     * Canonicalize an array for comparison.
     *
     * @param array $arr
     *
     * @return array
     */
    protected function canonicalizeArray(array $arr)
    {
        ksort($arr);

        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = $this->canonicalizeArray($v);
            } elseif (is_object($v)) {
                $arr[$k] = $this->canonicalizeArray((array) $v);
            }
        }

        return $arr;
    }
}
