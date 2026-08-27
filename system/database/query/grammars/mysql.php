<?php

namespace System\Database\Query\Grammars;

defined('DS') or exit('No direct access.');

use System\Database\Query;

class MySQL extends Grammar
{
    /**
     * Contains the wrapper format.
     *
     * @var string
     */
    protected $wrapper = '`%s`';

    /**
     * Get the sql used to order the results randomly.
     *
     * @param string $seed
     *
     * @return string
     */
    public function random($seed = '')
    {
        return ('' === (string) $seed) ? 'RAND()' : 'RAND(' . (int) $seed . ')';
    }

    /**
     * Compile an INSERT statement that silently skips clashing records.
     *
     * @param Query $query
     * @param array $values
     *
     * @return string
     */
    public function insert_ignore(Query $query, array $values)
    {
        return preg_replace('/^INSERT INTO /', 'INSERT IGNORE INTO ', $this->insert($query, $values), 1);
    }

    /**
     * Compile the row locking clause.
     * MySQL only learned FOR SHARE in 8.0, so the older spelling is used to
     * stay compatible with the versions this framework still supports.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function lock(Query $query)
    {
        if (is_string($query->lock)) {
            return $query->lock;
        }

        return $query->lock ? 'FOR UPDATE' : 'LOCK IN SHARE MODE';
    }
}
