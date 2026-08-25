<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Outputs
{
    /**
     * Karakter byte order mark (BOM).
     */
    const BOM = "\xEF\xBB\xBF";

    /**
     * List info errpr.
     *
     * @var array
     */
    private $list = [];

    public static function enable()
    {
        $me = new static();
        $me->start();
    }

    public function start()
    {
        foreach (get_included_files() as $file) {
            // Note: the handle has to be checked and closed. fopen() answers FALSE
            // for an unreadable file (a TypeError for fread() on PHP 8), and
            // leaving every handle open leaks one per included file.
            $handle = @fopen($file, 'r');

            if (false === $handle) {
                continue;
            }

            $head = fread($handle, 3);
            fclose($handle);

            if (self::BOM === $head) {
                // The fourth element is the call stack, which the handler and the
                // renderer below both read - so it has to be there.
                $this->list[] = [$file, 1, self::BOM, []];
            }
        }

        ob_start([$this, 'handler'], 1);
    }

    /**
     * @param string $s
     * @param int    $phase
     *
     * @return string|null
     *
     * @internal
     */
    public function handler($s, $phase)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        if (isset($trace[0]['file'], $trace[0]['line'])) {
            $stack = $trace;
            unset($stack[0]['line'], $stack[0]['args']);

            $i = count($this->list);

            if ($i && isset($this->list[$i - 1][3]) && $this->list[$i - 1][3] === $stack) {
                $this->list[$i - 1][2] .= $s;
            } else {
                $this->list[] = [$trace[0]['file'], $trace[0]['line'], $s, $stack];
            }
        }

        if ($phase === PHP_OUTPUT_HANDLER_FINAL) {
            return $this->renderHtml();
        }
    }

    private function renderHtml()
    {
        $res = '<style>code, pre {white-space:nowrap} a {text-decoration:none} pre {color:gray;display:inline} big {color:red}</style><code>';

        foreach ($this->list as $item) {
            $stack = [];

            foreach (array_slice(isset($item[3]) ? $item[3] : [], 1) as $t) {
                $t += ['class' => '', 'type' => '', 'function' => ''];
                $stack[] = $t['class'] . $t['type'] . $t['function'] . '()'
                    . (isset($t['file'], $t['line']) ? ' in ' . basename($t['file']) . ':' . $t['line'] : '');
            }

            $res .= '<span title="' . Helpers::escapeHtml(implode("\n", $stack)) . '">'
                . '<span>' . $item[0] . ($item[1] ? ":$item[1]" : '') . '</span> '
                . str_replace(self::BOM, '<big>BOM</big>', Dumper::toHtml($item[2]))
                . "</span><br>\n";
        }

        return $res . '</code>';
    }
}
