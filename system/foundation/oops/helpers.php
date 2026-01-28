<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Helpers
{


    public static function formatHtml($mask)
    {
        $args = func_get_args();

        return preg_replace_callback('#%#', function () use (&$args, &$count) {
            return self::escapeHtml($args[++$count]);
        }, $mask);
    }

    public static function escapeHtml($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function findTrace(array $trace, $method, &$index = null)
    {
        $m = explode('::', $method);

        foreach ($trace as $i => $item) {
            if (
                isset($item['function'])
                && $item['function'] === end($m)
                && isset($item['class']) === isset($m[1])
                && (!isset($item['class']) || '*' === $m[0] || is_a($item['class'], $m[0], true))
            ) {
                $index = $i;
                return $item;
            }
        }
    }

    /**
     * @return string
     */
    public static function getClass($obj)
    {
        return explode("\x00", get_class($obj))[0];
    }

    /**
     * Perbaiki stack exception.
     *
     * @param object $e
     *
     * @return object
     */
    public static function fixStack($e)
    {
        if (function_exists('xdebug_get_function_stack')) {
            $stack = [];
            /** @disregard */
            $rows = array_slice(array_reverse(xdebug_get_function_stack()), 2, -1);

            foreach ($rows as $row) {
                $frame = [
                    'file' => $row['file'],
                    'line' => $row['line'],
                    'function' => isset($row['function']) ? $row['function'] : '*unknown*',
                    'args' => [],
                ];

                if (!empty($row['class'])) {
                    $frame['type'] = (isset($row['type']) && 'dynamic' === $row['type']) ? '->' : '::';
                    $frame['class'] = $row['class'];
                }

                $stack[] = $frame;
            }

            $ref = new \ReflectionProperty('Exception', 'trace');
            /** @disregard */
            $ref->setAccessible(true);
            $ref->setValue($e, $stack);
        }

        return $e;
    }

    /** @internal */
    public static function fixEncoding($s)
    {
        return htmlspecialchars_decode(htmlspecialchars($s, ENT_NOQUOTES | ENT_IGNORE, 'UTF-8'), ENT_NOQUOTES);
    }

    /** @internal */
    public static function errorTypeToString($type)
    {
        $types = [
            E_ERROR => 'Fatal Error',
            E_USER_ERROR => 'User Error',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_CORE_ERROR => 'Core Error',
            E_COMPILE_ERROR => 'Compile Error',
            E_PARSE => 'Parse Error',
            E_WARNING => 'Warning',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_WARNING => 'User Warning',
            E_NOTICE => 'Notice',
            E_USER_NOTICE => 'User Notice',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
            2048 => 'Strict Standards',
        ];

        return isset($types[$type]) ? $types[$type] : 'Unknown error';
    }

    /** @internal */
    public static function getSource()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return (!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https://' : 'http://')
                . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $_SERVER['REQUEST_URI'];
        }

        return 'CLI (PID: ' . getmypid() . ')' . (empty($_SERVER['argv']) ? '' : ': ' . implode(' ', $_SERVER['argv']));
    }

    /** @internal */
    public static function improveException($e)
    {
        $message = $e->getMessage();

        if (!($e instanceof \Error) && !($e instanceof \ErrorException)) {
            // ..
        } elseif (preg_match('#^Call to undefined function (\S+\\\\)?(\w+)\(#', $message, $m)) {
            $funcs = array_merge(get_defined_functions()['internal'], get_defined_functions()['user']);
            $hint = self::getSuggestion($funcs, $m[1] . $m[2]);
            $hint = $hint ?: self::getSuggestion($funcs, $m[2]);
            $message = "Call to undefined function $m[2](), did you mean $hint()?";
            $replace = ["$m[2](", "$hint("];
        } elseif (preg_match('#^Call to undefined method ([\w\\\\]+)::(\w+)#', $message, $m)) {
            $hint = get_class_methods($m[1]);
            $hint = self::getSuggestion($hint ?: [], $m[2]);
            $message .= ", did you mean $hint()?";
            $replace = ["$m[2](", "$hint("];
        } elseif (preg_match('#^Undefined variable: (\w+)#', $message, $m) && (null !== Context::getContext($e))) {
            $ctx = Context::getContext($e);
            $hint = self::getSuggestion(array_keys($ctx), $m[1]);
            $message = "Undefined variable $$m[1], did you mean $$hint?";
            $replace = ["$$m[1]", "$$hint"];
        } elseif (preg_match('#^Undefined property: ([\w\\\\]+)::\$(\w+)#', $message, $m)) {
            $rc = new \ReflectionClass($m[1]);
            $items = array_diff(
                $rc->getProperties(\ReflectionProperty::IS_PUBLIC),
                $rc->getProperties(\ReflectionProperty::IS_STATIC)
            );

            $hint = self::getSuggestion($items, $m[2]);
            $message .= ", did you mean $$hint?";
            $replace = ["->$m[2]", "->$hint"];
        } elseif (preg_match('#^Access to undeclared static property: ([\w\\\\]+)::\$(\w+)#', $message, $m)) {
            $rc = new \ReflectionClass($m[1]);
            $items = array_intersect(
                $rc->getProperties(\ReflectionProperty::IS_PUBLIC),
                $rc->getProperties(\ReflectionProperty::IS_STATIC)
            );

            $hint = self::getSuggestion($items, $m[2]);
            $message .= ", did you mean $$hint?";
            $replace = ["::$$m[2]", "::$$hint"];
        }

        if (isset($hint)) {
            $ref = new \ReflectionProperty($e, 'message');
            /** @disregard */
            $ref->setAccessible(true);
            $ref->setValue($e, $message);
            // Store oopsAction via Context helper
            // Editor link removed
        }
    }

    /** @internal */
    public static function improveError($message, array $context = [])
    {
        if (preg_match('#^Undefined variable: (\w+)#', $message, $m) && $context) {
            $hint = self::getSuggestion(array_keys($context), $m[1]);

            return $hint ? "Undefined variable $$m[1], did you mean $$hint?" : $message;
        } elseif (preg_match('#^Undefined property: ([\w\\\\]+)::\$(\w+)#', $message, $m)) {
            $rc = new \ReflectionClass($m[1]);
            $items = array_diff(
                $rc->getProperties(\ReflectionProperty::IS_PUBLIC),
                $rc->getProperties(\ReflectionProperty::IS_STATIC)
            );

            $hint = self::getSuggestion($items, $m[2]);

            return $hint ? $message . ", did you mean $$hint?" : $message;
        }

        return $message;
    }

    /** @internal */
    public static function guessClassFile($class)
    {
        $segments = explode(DIRECTORY_SEPARATOR, $class);
        $res = null;
        $max = 0;

        foreach (get_declared_classes() as $class) {
            $parts = explode(DIRECTORY_SEPARATOR, $class);

            foreach ($parts as $i => $part) {
                if (!isset($segments[$i]) || $part !== $segments[$i]) {
                    break;
                }
            }

            if ($i > $max && ($file = (new \ReflectionClass($class))->getFileName())) {
                $max = $i;
                $res = array_merge(
                    array_slice(explode(DIRECTORY_SEPARATOR, $file), 0, $i - count($parts)),
                    array_slice($segments, $i)
                );

                $res = implode(DIRECTORY_SEPARATOR, $res) . '.php';
            }
        }

        return $res;
    }

    /**
     * Temukan saran bantuan untuk pesan error.
     *
     * @return string|null
     *
     * @internal
     */
    public static function getSuggestion(array $items, $value)
    {
        $best = null;
        $min = (mb_strlen($value, '8bit') / 4 + 1) * 10 + .1;

        foreach (array_unique($items, SORT_REGULAR) as $item) {
            $item = is_object($item) ? $item->getName() : $item;

            if (($len = levenshtein($item, $value, 10, 11, 10)) > 0 && $len < $min) {
                $min = $len;
                $best = $item;
            }
        }

        return $best;
    }

    /** @internal */
    public static function isHtmlMode()
    {
        return empty($_SERVER['HTTP_X_REQUESTED_WITH']) && empty($_SERVER['HTTP_X_OOPS_AJAX'])
            && PHP_SAPI !== 'cli'
            && !preg_match('#^Content-Type: (?!text/html)#im', implode("\n", headers_list()));
    }

    /** @internal */
    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_OOPS_AJAX']) && preg_match('#^\w{10}\z#', $_SERVER['HTTP_X_OOPS_AJAX']);
    }

    /** @internal */
    public static function getNonce()
    {
        return preg_match('#^Content-Security-Policy(?:-Report-Only)?:.*\sscript-src\s+(?:[^;]+\s)?\'nonce-([\w+/]+=*)\'#mi', implode("\n", headers_list()), $m) ? $m[1] : null;
    }
}
