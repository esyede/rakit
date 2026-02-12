<?php

namespace System\Database\Exceptions;

defined('DS') or exit('No direct access.');

class MassAssignmentException extends DatabaseException
{
    /**
     * Contains the model name that caused the mass assignment error.
     *
     * @var string
     */
    protected $model;

    /**
     * Contains the attributes that were not fillable.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Constructor.
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
     * Get the model name that caused the mass assignment error.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the attributes that were not fillable.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
