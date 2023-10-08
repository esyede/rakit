<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Defaults
{
    const SQL_KEYWORDS1 = 'SELECT|(?:ON\s+DUPLICATE\s+KEY)?UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|CALL|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';
    const SQL_KEYWORDS2 = 'ALL|DISTINCT|DISTINCTROW|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|[RI]?LIKE|REGEXP|TRUE|FALSE';

    public $data;
    public $time;
    public $profiler;
    public $cpuUsage;

    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Render tag HTML milik tab default.
     *
     * @return string
     */
    public function getTab()
    {
        ob_start(function () {
            // ..
        });

        $data = $this->data;
        require __DIR__ . '/assets/bar/' . $this->id . '.tab.phtml';

        return ob_get_clean();
    }

    /**
     * Render tag HTML untuk panel default.
     *
     * @return string
     */
    public function getPanel()
    {
        ob_start(function () {
            // ..
        });

        if (is_file(__DIR__ . '/assets/bar/' . $this->id . '.panel.phtml')) {
            $data = $this->data;
            require __DIR__ . '/assets/bar/' . $this->id . '.panel.phtml';
        }

        return ob_get_clean();
    }

    /**
     * Tips - tips penggunaan query sql.
     *
     * @param string $sql
     *
     * @return array
     */
    public static function sqlHints($sql)
    {
        $hints = [];

        if (preg_match('/^\\s*SELECT\\s*`?[a-zA-Z0-9]*`?\\.?\\*/i', $sql)) {
            $hints[] = 'Use <code>SELECT *</code> only if you need all columns from table';
        }

        if (preg_match('/ORDER BY RAND()/i', $sql)) {
            $hints[] = '<code>ORDER BY RAND()</code> is slow, try to avoid if you can.
            You can <a href="https://stackoverflow.com/questions/2663710/how-does-mysqls-order-by-rand-work">read this</a>
            or <a href="https://stackoverflow.com/questions/1244555/how-can-i-optimize-mysqls-order-by-rand-function">this</a>';
        }

        if (false !== strpos($sql, '!=')) {
            $hints[] = 'The <code>!=</code> operator is not standard. Use the <code>&lt;&gt;</code> operator to test for inequality instead.';
        }
        if (preg_match('/SELECT\\s/i', $sql) && false === stripos($sql, 'WHERE')) {
            $hints[] = 'The <code>SELECT</code> statement has no <code>WHERE</code> clause and could examine many more rows than intended';
        }

        if (preg_match('/LIMIT\\s/i', $sql) && false === stripos($sql, 'ORDER BY')) {
            $hints[] = '<code>LIMIT</code> without <code>ORDER BY</code> causes non-deterministic results, depending on the query execution plan';
        }

        if (preg_match('/LIKE\\s[\'"](%.*?)[\'"]/i', $sql, $matches)) {
            $hints[] = 'An argument has a leading wildcard character: <code>' . $matches[1] . '</code>.
            The predicate with this argument is not sargable and cannot use an index if one exists.';
        }

        if (preg_match('/ORDER BY RAND()/i', $sql)) {
            $hints[] = '<code>ORDER BY RAND()</code> is slow, try to avoid if you can.
            You can <a href="https://stackoverflow.com/questions/2663710/how-does-mysqls-order-by-rand-work">read this</a>
            or <a href="https://stackoverflow.com/questions/1244555/how-can-i-optimize-mysqls-order-by-rand-function">this</a>';
        }

        return $hints;
    }

    /**
     * Code highlighter untuk sintaks sql.
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @return string
     */
    public static function sqlHighlight($sql, array $bindings = [])
    {
        $sql = " $sql ";
        $sql = preg_replace('#(?<=[\\s,(])(' . static::SQL_KEYWORDS1 . ')(?=[\\s,)])#i', "\n\$1", $sql);
        $sql = preg_replace('#[ \t]{2,}#', ' ', $sql);
        $sql = htmlspecialchars($sql, ENT_IGNORE, 'UTF-8');
        $sql = preg_replace_callback('#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])(' . static::SQL_KEYWORDS1 . ')(?=[\\s,)])|(?<=[\\s,(=])(' . static::SQL_KEYWORDS2 . ')(?=[\\s,)=])#is', function ($matches) {
            if (!empty($matches[1])) { // komentar
                return '<em style="color:gray">' . $matches[1] . '</em>';
            } elseif (!empty($matches[2])) { // error
                return '<strong style="color:red">' . $matches[2] . '</strong>';
            } elseif (!empty($matches[3])) { // keyword - keyword penting
                return '<strong style="color:blue; text-transform: uppercase;">' . $matches[3] . '</strong>';
            } elseif (!empty($matches[4])) { // keyword lainnya
                return '<strong style="color:green">' . $matches[4] . '</strong>';
            }
        }, $sql);

        $bindings = array_map(function ($binding) {
            if (is_array($binding)) {
                $binding = implode(', ', array_map(function ($value) {
                    return is_string($value) ? htmlspecialchars('\'' . $value . '\'', ENT_NOQUOTES, 'UTF-8') : $value;
                }, $binding));

                return htmlspecialchars('(' . $binding . ')', ENT_NOQUOTES, 'UTF-8');
            }

            if (
                is_string($binding)
                && (preg_match('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u', $binding) || preg_last_error())
            ) {
                return '<i title="Length ' . mb_strlen($binding, '8bit') . ' bytes">&lt;binary&gt;</i>';
            }

            if (is_string($binding)) {
                $text = htmlspecialchars('\'' . $binding . '\'', ENT_NOQUOTES, 'UTF-8');
                return '<span title="Length ' . mb_strlen($text, '8bit') . ' characters">' . $text . '</span>';
            }

            if (is_resource($binding)) {
                $type = get_resource_type($binding);

                if ($type === 'stream') {
                    $info = stream_get_meta_data($binding);
                }

                return '<i' . (isset($info['uri']) ? ' title="' . htmlspecialchars($info['uri'], ENT_NOQUOTES, 'UTF-8') . '"' : null)
                    . '>&lt;' . htmlspecialchars($type, ENT_NOQUOTES, 'UTF-8') . ' resource&gt;</i>';
            }

            if ($binding instanceof \DateTime) {
                return htmlspecialchars('\'' . $binding->format('Y-m-d H:i:s') . '\'', ENT_NOQUOTES, 'UTF-8');
            }

            return htmlspecialchars($binding, ENT_NOQUOTES, 'UTF-8');
        }, $bindings);

        $sql = str_replace(['%', '?'], ['%%', '%s'], $sql);

        return '<div><code>' . nl2br(trim(vsprintf($sql, $bindings))) . '</code></div>';
    }
}
