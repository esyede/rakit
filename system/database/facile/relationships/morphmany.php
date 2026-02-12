<?php

namespace System\Database\Facile\Relationships;

defined('DS') or exit('No direct access.');

use System\Database\Facile\Model;

class MorphMany extends HasMany
{
    /**
     * Contains morph type.
     *
     * @var string
     */
    protected $type;

    /**
     * Berisi morph id.
     *
     * @var string
     */
    protected $id;

    /**
     * Constructor.
     *
     * @param Model  $model
     * @param string $associated
     * @param string $type
     * @param string $id
     * @param string $foreign
     */
    public function __construct($model, $associated, $type, $id, $foreign = null)
    {
        $this->type = $type;
        $this->id = $id;
        parent::__construct($model, $associated, $foreign);
    }

    /**
     * Set the appropriate constraint on the relational query.
     */
    protected function constrain()
    {
        $this->table
            ->where($this->type, '=', get_class($this->base))
            ->where($this->id, '=', $this->base->get_key());
    }

    /**
     * Set the foreign key on a given model.
     *
     * @param Model $model
     */
    protected function set_foreign_key(Model $model)
    {
        $model->set_attribute($this->type, get_class($this->base));
        $model->set_attribute($this->id, $this->base->get_key());
    }
}
