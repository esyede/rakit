<?php

namespace System\Foundation\Faker;

defined('DS') or exit('No direct access.');

class Unique
{
    protected $generator;
    protected $max_retries;
    protected $uniques = [];

    public function __construct($generator, $max_retries = 10000)
    {
        $this->generator = $generator;
        $this->max_retries = $max_retries;
    }

    public function reset($name = null)
    {
        if (is_null($name)) {
            $this->uniques = [];
            return;
        }

        unset($this->uniques[$name]);
    }

    public function setMaxRetries($max)
    {
        $this->max_retries = (int) $max;
    }

    public function getMaxRetries()
    {
        return $this->max_retries;
    }

    public function __get($attribute)
    {
        return $this->__call($attribute, []);
    }

    public function __call($name, array $arguments)
    {
        if (!isset($this->uniques[$name])) {
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

    protected function makeKey($value)
    {
        if (is_null($value)) {
            return 'null:';
        }

        if (is_scalar($value)) {
            return gettype($value) . ':' . (string) $value;
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            $canonical = $this->canonicalizeArray($value);
            $json = json_encode($canonical);

            if (false !== $json) {
                return 'json:' . $json;
            }
        }

        return 'ser:' . serialize($value);
    }

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
