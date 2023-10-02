<?php

namespace System\Database\Query\Grammars;

defined('DS') or exit('No direct access.');

use System\Database\Query;

class SQLServer extends Grammar
{
    /**
     * Identifier keyword untuk engine database.
     *
     * @var string
     */
    protected $wrapper = '[%s]';

    /**
     * Format baku untuk menyimpan DateTime.
     *
     * @var string
     */
    public $datetime = 'Y-m-d H:i:s.000';

    /**
     * Compile statement SELECT dari instance query.
     *
     * @param Query $query
     *
     * @return string
     */
    public function select(Query $query)
    {
        $sql = parent::components($query);

        if ($query->offset > 0) {
            return $this->ansi_offset($query, $sql);
        }

        return $this->concatenate($sql);
    }

    /**
     * Compile klausa SELECT.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function selects(Query $query)
    {
        if (!is_null($query->aggregate)) {
            return;
        }

        $select = $query->distinct ? 'SELECT DISTINCT ' : 'SELECT ';

        if ($query->limit > 0 && $query->offset <= 0) {
            $select .= 'TOP ' . $query->limit . ' ';
        }

        return $select . $this->columnize($query->selects);
    }

    /**
     * Buat klausa OFFSET mengikuti standar ANSI.
     *
     * @param Query $query
     * @param array $components
     *
     * @return array
     */
    protected function ansi_offset(Query $query, $components)
    {
        if (!isset($components['orderings'])) {
            $components['orderings'] = 'ORDER BY (SELECT 0)';
        }

        $orderings = $components['orderings'];
        $components['selects'] .= ', ROW_NUMBER() OVER (' . $orderings . ') AS RowNum';

        unset($components['orderings']);

        $start = $query->offset + 1;

        if ($query->limit > 0) {
            $finish = $query->offset + $query->limit;
            $constraint = 'BETWEEN ' . $start . ' AND ' . $finish;
        } else {
            $constraint = '>= ' . $start;
        }

        $sql = $this->concatenate($components);

        return 'SELECT * FROM (' . $sql . ') AS TempTable WHERE RowNum ' . $constraint;
    }

    /**
     * Compile klausa UPDATE.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function limit(Query $query)
    {
        return '';
    }

    /**
     * Compile klausa OFFSET.
     *
     * @param Query $query
     *
     * @return string
     */
    protected function offset(Query $query)
    {
        return '';
    }
}
