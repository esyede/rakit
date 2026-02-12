<?php

namespace System\Database\Exceptions;

defined('DS') or exit('No direct access.');

class ModelNotFoundException extends DatabaseException
{
    /**
     * Contains the model name that was not found.
     *
     * @var string
     */
    protected $model;

    /**
     * Contains the IDs that were searched for.
     *
     * @var array
     */
    protected $ids;

    /**
     * Constructor.
     *
     * @param string $model
     * @param array  $ids
     */
    public function __construct($model, array $ids = [])
    {
        $this->model = $model;
        $this->ids = $ids;

        parent::__construct(vsprintf(
            'No query results for model [%s]' . (empty($ids) ? '%s' : ' with IDs: %s'),
            [$model, empty($ids) ? '' : implode(', ', $ids)]
        ));
    }

    /**
     * Get the model name that was not found.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the IDs that were searched for.
     *
     * @return array
     */
    public function getIds()
    {
        return $this->ids;
    }
}
