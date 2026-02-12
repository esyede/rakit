<?php

namespace System\Database\Query\Grammars;

defined('DS') or exit('No direct access.');

use System\Database\Query;

class SQLServer extends Grammar
{
    /**
     * Contains the wrapper format.
     *
     * @var string
     */
    protected $wrapper = '[%s]';

    /**
     * Format for datetime columns.
     *
     * @var string
     */
    public $datetime = 'Y-m-d H:i:s.000';

    /**
     * Compile the SELECT statement.
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
     * Compile the SELECT clause.
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
     * Make an ANSI-compliant OFFSET clause.
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
     * Compile the LIMIT clause.
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
     * Compile the OFFSET clause.
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
