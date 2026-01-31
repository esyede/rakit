<?php

namespace System\Database\Exceptions;

defined('DS') or exit('No direct access.');

class MassAssignmentException extends DatabaseException
{
    /**
     * Nama model yang mengalami mass assignment error.
     *
     * @var string
     */
    protected $model;

    /**
     * Atribut yang tidak diizinkan.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Buat instance exception baru.
     *
     * @param string $model
     * @param array  $attributes
     */
    public function __construct($model, array $attributes)
    {
        $this->model = $model;
        $this->attributes = $attributes;

        parent::__construct(vsprintf(
            'Mass assignment attempted on [%s] for non-fillable attributes: %s',
            [$model, implode(', ', $attributes)]
        ));
    }

    /**
     * Ambil nama model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Ambil atribut yang tidak diizinkan.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
