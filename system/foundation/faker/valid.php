<?php

namespace System\Foundation\Faker;

defined('DS') or exit('No direct access.');

class Valid
{
    /** @var \System\Foundation\Faker\Generator */
    protected $generator;

    /** @var callable */
    protected $validator;

    /** @var int */
    protected $max_retries;

    /**
     * Create a new Valid instance.
     *
     * @param \System\Foundation\Faker\Generator $generator
     * @param callable|null $validator
     * @param int $max_retries
     */
    public function __construct(Generator $generator, $validator = null, $max_retries = 10000)
    {
        if (is_null($validator)) {
            $validator = function () {
                return true;
            };
        } elseif (! is_callable($validator)) {
            throw new \InvalidArgumentException('valid() only accepts callables as first argument');
        }

        $this->generator = $generator;
        $this->validator = $validator;
        $this->max_retries = $max_retries;
    }

    /**
     * Magic getter to get valid values.
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
     * Magic call to get valid values.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        $retry = 0;

        do {
            $result = call_user_func_array([$this->generator, $name], $arguments);
            ++$retry;

            if ($retry > $this->max_retries) {
                throw new \OverflowException(sprintf('Maximum retries of %s reached without finding a unique value.', $this->max_retries));
            }
        } while (! call_user_func($this->validator, $result));
        return $result;
    }
}
