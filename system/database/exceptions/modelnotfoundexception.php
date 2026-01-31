<?php

namespace System\Database\Exceptions;

defined('DS') or exit('No direct access.');

class ModelNotFoundException extends DatabaseException
{
    /**
     * Nama model yang tidak ditemukan.
     *
     * @var string
     */
    protected $model;

    /**
     * IDs yang dicari.
     *
     * @var array
     */
    protected $ids;

    /**
     * Buat instance exception baru.
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
     * Ambil nama model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Ambil IDs yang dicari.
     *
     * @return array
     */
    public function getIds()
    {
        return $this->ids;
    }
}
