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
     * Compile an INSERT that updates the records already there. VALUES() works on
     * MySQL 5.x, 8.x and MariaDB alike.
     *
     * @param Query $query
     * @param array $values
     * @param array $unique_by
     * @param array $update
     *
     * @return string
     */
    public function upsert(Query $query, array $values, array $unique_by, array $update)
    {
        $sets = [];

        foreach ($update as $column) {
            $sets[] = $this->wrap($column).' = VALUES('.$this->wrap($column).')';
        }

        return $this->insert($query, $values).' ON DUPLICATE KEY UPDATE '.implode(', ', $sets);
    }

    /**
     * Compile the row locking clause, in the pre-8.0 spelling of FOR SHARE.
     * NOWAIT and SKIP LOCKED need MySQL 8.0, which also knows FOR SHARE.
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

        if ($query->lock_option) {
            return parent::lock($query);
        }

        return $query->lock ? 'FOR UPDATE' : 'LOCK IN SHARE MODE';
    }

    /**
     * Compile the OFFSET clause; without a LIMIT it is invalid, so add the largest one.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function offset(Query $query)
    {
        $sql = parent::offset($query);
        return is_null($query->limit) ? 'LIMIT 18446744073709551615 '.$sql : $sql;
    }
}
