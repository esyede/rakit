<?php

namespace System\Foundation\Faker;

defined('DS') or exit('No direct script access.');

class Unique
{
    protected $generator;
    protected $max_retries;
    protected $uniques = [];

    public function __construct(Generator $generator, $max_retries = 10000)
    {
        $this->generator = $generator;
        $this->max_retries = $max_retries;
    }

    public function __get($attribute)
    {
        return $this->__call($attribute, []);
    }

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
                    'Maximum retries of %s reached without finding a unique value.', $this->max_retries
                ));
            }
        } while (array_key_exists(serialize($result), $this->uniques[$name]));

        $this->uniques[$name][serialize($result)] = null;

        return $result;
    }
}
