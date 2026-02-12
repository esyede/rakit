<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Defaults
{
    public $data;
    public $time;
    public $profiler;
    public $cpuUsage;

    private $id;

    private static $sqlKeywords = [
        'keywords1' => [
            // Primary SQL keywords (DML, DDL, DCL)
            'SELECT', 'UPDATE', 'INSERT', 'INSERT\s+INTO', 'REPLACE', 'REPLACE\s+INTO',
            'DELETE', 'TRUNCATE', 'UNION', 'UNION\s+ALL',
            'FROM', 'WHERE', 'HAVING', 'GROUP\s+BY', 'ORDER\s+BY', 'LIMIT', 'OFFSET',
            'SET', 'VALUES', 'ON\s+DUPLICATE\s+KEY\s+UPDATE',
            'JOIN', 'LEFT\s+JOIN', 'RIGHT\s+JOIN', 'INNER\s+JOIN', 'OUTER\s+JOIN',
            'FULL\s+JOIN', 'CROSS\s+JOIN', 'NATURAL\s+JOIN',
            'CREATE', 'ALTER', 'DROP', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN',
            'GRANT', 'REVOKE', 'CALL', 'EXECUTE', 'BEGIN', 'COMMIT', 'ROLLBACK'
        ],
        'keywords2' => [
            // Secondary SQL keywords (clauses, functions, operators)
            'ALL', 'DISTINCT', 'DISTINCTROW', 'IGNORE', 'AS', 'USING', 'ON',
            'AND', 'OR', 'XOR', 'IN', 'IS', 'NOT', 'NULL',
            'LIKE', 'RLIKE', 'ILIKE', 'REGEXP', 'BETWEEN', 'EXISTS',
            'TRUE', 'FALSE', 'ASC', 'DESC',
            'CASE', 'WHEN', 'THEN', 'ELSE', 'END', 'IF', 'IFNULL', 'COALESCE',
            'COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'GROUP_CONCAT',
            'CAST', 'CONVERT', 'CONCAT', 'LENGTH', 'SUBSTRING',
            'NOW', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP'
        ],
    ];

    private static $sqlKeywordsCache = null;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Render default tab.
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
     * Render default panel.
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
     * Tips - tips penggunaan query SQL dengan severity levels.
     *
     * @param string $sql
     *
     * @return array
     */
    public static function sqlHints($sql)
    {
        $hints = [];
        $hints = array_merge($hints, static::checkPerformanceIssues($sql)); // Performance Issues
        $hints = array_merge($hints, static::checkBestPractices($sql)); // Best Practices
        $hints = array_merge($hints, static::checkSecurityIssues($sql)); // Security Issues
        $hints = array_merge($hints, static::checkReadabilityIssues($sql)); // Readability Issues
        return $hints;
    }

    /**
     * Check performance-related issues.
     *
     * @param string $sql
     *
     * @return array
     */
    private static function checkPerformanceIssues($sql)
    {
        $hints = [];

        // SELECT * usage (except COUNT(*))
        if (preg_match('/^\\s*SELECT\\s+\\*\\s+FROM/i', $sql) && !preg_match('/COUNT\\s*\\(\\s*\\*\\s*\\)/i', $sql)) {
            $hints[] = [
                'severity' => 'warning',
                'category' => 'performance',
                'message' => 'Use <code>SELECT *</code> only if you need all columns. Specify only needed columns for better performance.'
            ];
        }

        // ORDER BY RAND()
        if (preg_match('/ORDER\\s+BY\\s+RAND\\s*\\(\\s*\\)/i', $sql)) {
            $hints[] = [
                'severity' => 'warning',
                'category' => 'performance',
                'message' => '<code>ORDER BY RAND()</code> is very slow on large tables. Consider using alternative approaches like ' .
                    '<a href="https://stackoverflow.com/questions/1244555/how-can-i-optimize-mysqls-order-by-rand-function" target="_blank">this solution</a>.',
            ];
        }

        // LIKE with leading wildcard
        if (preg_match('/LIKE\\s+[\'"](%[^%]+)[\'"]/i', $sql, $matches)) {
            $hints[] = [
                'severity' => 'warning',
                'category' => 'performance',
                'message' => 'Leading wildcard in <code>LIKE</code> pattern: <code>' . htmlspecialchars($matches[1]) . '</code>.
                This prevents index usage and causes full table scan. Consider full-text search for better performance.'
            ];
        }

        // OFFSET with large values
        if (preg_match('/OFFSET\\s+(\\d+)/i', $sql, $matches) && (int)$matches[1] > 1000) {
            $hints[] = [
                'severity' => 'warning',
                'category' => 'performance',
                'message' => 'Large <code>OFFSET</code> value (' . $matches[1] . ') can be slow. Consider using keyset pagination instead.',
            ];
        }

        // Subquery in SELECT list
        if (preg_match('/SELECT\\s+[^,]*\\(\\s*SELECT\\s+/i', $sql)) {
            $hints[] = [
                'severity' => 'info',
                'category' => 'performance',
                'message' => 'Subquery in SELECT list may cause N+1 query problem. Consider using <code>JOIN</code> instead.',
            ];
        }

        // Multiple OR conditions (3+)
        if (preg_match_all('/\\bOR\\b/i', $sql, $matches) && count($matches[0]) >= 3) {
            $hints[] = [
                'severity' => 'info',
                'category' => 'performance',
                'message' => 'Multiple <code>OR</code> conditions detected (' . count($matches[0]) . '). Consider using <code>IN</code> clause for better performance and readability.',
            ];
        }

        // DISTINCT without need
        if (preg_match('/SELECT\\s+DISTINCT\\s+/i', $sql) && !preg_match('/JOIN/i', $sql)) {
            $hints[] = [
                'severity' => 'info',
                'category' => 'performance',
                'message' => '<code>DISTINCT</code> without <code>JOIN</code> might indicate data quality issues. Ensure it\'s actually needed.',
            ];
        }

        // NOT IN with subquery
        if (preg_match('/NOT\\s+IN\\s*\\(\\s*SELECT/i', $sql)) {
            $hints[] = [
                'severity' => 'warning',
                'category' => 'performance',
                'message' => '<code>NOT IN</code> with subquery can be slow and may have NULL handling issues. Consider using <code>NOT EXISTS</code> or <code>LEFT JOIN</code> instead.',
            ];
        }

        return $hints;
    }

    /**
     * Check SQL best practices.
     *
     * @param string $sql
     *
     * @return array
     */
    private static function checkBestPractices($sql)
    {
        $hints = [];

        // SELECT without WHERE
        if (preg_match('/^\\s*SELECT\\s+/i', $sql) && !preg_match('/WHERE|LIMIT/i', $sql)) {
            $hints[] = [
                'severity' => 'warning',
                'category' => 'best-practice',
                'message' => 'The <code>SELECT</code> statement has no <code>WHERE</code> or <code>LIMIT</code> clause. This will scan all rows in the table.',
            ];
        }

        // LIMIT without ORDER BY
        if (preg_match('/LIMIT\\s+\\d+/i', $sql) && !preg_match('/ORDER\\s+BY/i', $sql)) {
            $hints[] = [
                'severity' => 'warning',
                'category' => 'best-practice',
                'message' => '<code>LIMIT</code> without <code>ORDER BY</code> produces non-deterministic results. The order may change between executions.',
            ];
        }

        // != operator (non-standard)
        if (strpos($sql, '!=') !== false) {
            $hints[] = [
                'severity' => 'info',
                'category' => 'best-practice',
                'message' => 'The <code>!=</code> operator is non-standard SQL. Use <code>&lt;&gt;</code> for better compatibility.',
            ];
        }

        // UNION without ALL
        if (preg_match('/\\bUNION\\s+(?!ALL)/i', $sql)) {
            $hints[] = [
                'severity' => 'info',
                'category' => 'best-practice',
                'message' => '<code>UNION</code> removes duplicates (slow operation). Use <code>UNION ALL</code> if duplicates are not a concern.',
            ];
        }

        // Using column numbers in ORDER BY
        if (preg_match('/ORDER\\s+BY\\s+\\d+/i', $sql)) {
            $hints[] = [
                'severity' => 'info',
                'category' => 'best-practice',
                'message' => 'Using column position numbers in <code>ORDER BY</code> is less maintainable. Use explicit column names instead.',
            ];
        }

        return $hints;
    }

    /**
     * Check security-related issues.
     *
     * @param string $sql
     *
     * @return array
     */
    private static function checkSecurityIssues($sql)
    {
        $hints = [];

        // Potential SQL injection (unquoted variables)
        if (preg_match('/[\'"]\\s*\\+|\\+\\s*[\'"]|\\$\\w+|\\{\\$/', $sql)) {
            $hints[] = [
                'severity' => 'error',
                'category' => 'security',
                'message' => 'Potential SQL injection vulnerability detected. Always use parameterized queries or prepared statements.',
            ];
        }

        return $hints;
    }

    /**
     * Check readability issues.
     *
     * @param string $sql
     *
     * @return array
     */
    private static function checkReadabilityIssues($sql)
    {
        $hints = [];

        // Very long query (>500 chars)
        if (mb_strlen($sql) > 500) {
            $hints[] = [
                'severity' => 'info',
                'category' => 'readability',
                'message' => 'Query is quite long (' . mb_strlen($sql) . ' characters). Consider breaking it into smaller parts or using views for better maintainability.',
            ];
        }

        return $hints;
    }

    /**
     * Detect N+1 query problems dari query log.
     *
     * @param array $queries
     *
     * @return array
     */
    public static function detectN1Queries(array $queries)
    {
        if (count($queries) < 10) {
            return []; // Need at least 10 queries to detect N+1
        }

        $results = [];
        $groups = [];

        // Step 1: Normalize queries dan group by pattern
        foreach ($queries as $index => $query) {
            $sql = $query['sql'];
            $normalizedSql = static::normalizeQuery($sql);

            if (!isset($groups[$normalizedSql])) {
                $groups[$normalizedSql] = [];
            }

            $groups[$normalizedSql][] = [
                'index' => $index,
                'sql' => $sql,
                'bindings' => isset($query['bindings']) ? $query['bindings'] : [],
                'time' => isset($query['time']) ? $query['time'] : 0,
            ];
        }

        // Step 2: Detect N+1 patterns
        foreach ($groups as $normalizedSql => $group) {
            $count = count($group);

            // Threshold: 3+ identical queries = potential N+1
            if ($count >= 3) {
                $totalTime = array_sum(array_column($group, 'time'));
                $avgTime = $totalTime / $count;
                // Estimate time saved if optimized (using JOIN/eager load)
                $estimatedOptimizedTime = $avgTime * 1.5; // Assume JOIN takes 1.5x single query
                $timeSaved = $totalTime - $estimatedOptimizedTime;
                $results[] = [
                    'severity' => $count >= 10 ? 'error' : 'warning',
                    'pattern' => $normalizedSql,
                    'count' => $count,
                    'queries' => $group,
                    'total_time' => round($totalTime, 2),
                    'avg_time' => round($avgTime, 2),
                    'time_saved' => round($timeSaved, 2),
                    'percentage' => round(($timeSaved / $totalTime) * 100, 1),
                ];
            }
        }

        // Sort by severity (count)
        usort($results, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return $results;
    }

    /**
     * Normalize SQL query untuk comparison.
     *
     * @param string $sql
     *
     * @return string
     */
    private static function normalizeQuery($sql)
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql)); // Normalize whitespace
        $sql = preg_replace('/\b\d+\b/', '?', $sql); // Replace numbers with placeholder
        $sql = preg_replace('/\'[^\']*\'/', '?', $sql); // Replace single-quoted strings
        $sql = preg_replace('/"[^"]*"/', '?', $sql); // Replace double-quoted strings
        $sql = preg_replace('/\?/', '?', $sql); // Replace question marks (prepared statement placeholders)
        $sql = strtolower($sql); // Case insensitive

        return $sql;
    }

    /**
     * Generate suggestion untuk fix N+1 query.
     *
     * @param string $pattern
     * @param int    $count
     *
     * @return string
     */
    public static function getN1Suggestion($pattern, $count)
    {
        $suggestions = [];

        // Detect query type
        if (preg_match('/select\s+.*\s+from\s+(\w+)\s+where/i', $pattern, $matches)) {
            $table = $matches[1];
            $suggestions[] = "<strong>Solutions:</strong>";
            $suggestions[] = "1. <strong>Eager Loading (ORM):</strong> If using Facile ORM, use <code>with()</code> method:"; // Eager Loading suggestion
            $suggestions[] = "<code>Model::with('relation')->get();</code>";
            $suggestions[] = "2. <strong>Use JOIN:</strong> Combine queries using JOIN instead of {$count} separate queries:"; // JOIN suggestion
            $suggestions[] = "<code>SELECT ... FROM main_table JOIN {$table} ON ...</code>";
            $suggestions[] = "3. <strong>Use IN clause:</strong> Fetch all at once:"; // IN clause suggestion
            $suggestions[] = "<code>SELECT * FROM {$table} WHERE id IN (?, ?, ...)</code>";
        }

        if (empty($suggestions)) {
            $suggestions[] = "Consider using <code>JOIN</code> or fetching related data in a single query instead of {$count} separate queries.";
        }

        return implode('<br>', $suggestions);
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
        // Cache keywords pattern untuk performa
        if (static::$sqlKeywordsCache === null) {
            static::$sqlKeywordsCache = [
                'keywords1' => implode('|', static::$sqlKeywords['keywords1']),
                'keywords2' => implode('|', static::$sqlKeywords['keywords2']),
            ];
        }

        $keywords1 = static::$sqlKeywordsCache['keywords1'];
        $keywords2 = static::$sqlKeywordsCache['keywords2'];

        // Format SQL untuk readability
        $sql = " $sql ";
        $sql = preg_replace('#(?<=[\\s,(])(' . $keywords1 . ')(?=[\\s,)])#i', "\n\$1", $sql);
        $sql = preg_replace('#[ \t]{2,}#', ' ', $sql);
        $sql = htmlspecialchars($sql, ENT_IGNORE, 'UTF-8');

        // Highlight SQL syntax
        $sql = preg_replace_callback(
            '#(/\\*.+?\\*/)|(\\-\\-.+?$)|(\\#.+?$)|(\\*\\*.+?\\*\\*)|' .
            '(?<=[\\s,(])(' . $keywords1 . ')(?=[\\s,)])|' .
            '(?<=[\\s,(=])(' . $keywords2 . ')(?=[\\s,)=])|' .
            '(\'[^\']*\')|' .  // String literals
            '(\\b\\d+\\b)#ims',  // Numbers
            function ($matches) {
                if (!empty($matches[1])) {
                    return '<em style="color:gray">' . $matches[1] . '</em>'; // Block comments /* */
                } elseif (!empty($matches[2]) || !empty($matches[3])) {
                    return '<em style="color:gray">' . ($matches[2] ?: $matches[3]) . '</em>'; // Line comments -- or #
                } elseif (!empty($matches[4])) {
                    return '<strong style="color:red">' . $matches[4] . '</strong>'; // Errors **text**
                } elseif (!empty($matches[5])) {
                    return '<strong style="color:blue; text-transform: uppercase;">' . $matches[5] . '</strong>'; // Primary keywords
                } elseif (!empty($matches[6])) {
                    return '<strong style="color:green">' . $matches[6] . '</strong>'; // Secondary keywords
                } elseif (!empty($matches[7])) {
                    return '<span style="color:#d14">' . $matches[7] . '</span>'; // String literals
                } elseif (!empty($matches[8])) {
                    return '<span style="color:#099">' . $matches[8] . '</span>'; // Numbers
                }
            },
            $sql
        );

        // Format bindings
        $bindings = array_map(function ($binding) {
            return call_user_func([__CLASS__, 'formatBinding'], $binding);
        }, $bindings);
        $sql = str_replace(['%', '?'], ['%%', '%s'], $sql);

        return '<div><code>' . nl2br(trim(vsprintf($sql, $bindings))) . '</code></div>';
    }

    /**
     * Format binding value untuk SQL highlight.
     *
     * @param mixed $binding
     *
     * @return string
     */
    private static function formatBinding($binding)
    {
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
            $length = mb_strlen($binding, '8bit');
            return '<span style="color:#d14" title="Length ' . $length . ' characters">' . $text . '</span>';
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
            return '<span style="color:#d14">' . htmlspecialchars('\'' . $binding->format('Y-m-d H:i:s') . '\'', ENT_NOQUOTES, 'UTF-8') . '</span>';
        }

        if (is_null($binding)) {
            return '<strong style="color:green">NULL</strong>';
        }

        if (is_bool($binding)) {
            return '<strong style="color:green">' . ($binding ? 'TRUE' : 'FALSE') . '</strong>';
        }

        return '<span style="color:#099">' . htmlspecialchars($binding, ENT_NOQUOTES, 'UTF-8') . '</span>';
    }
}
