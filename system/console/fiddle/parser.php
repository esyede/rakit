<?php

namespace System\Console\Fiddle;

defined('DS') or exit('No direct access.');

class Parser
{
    private $initials;
    private $pairs = [
        '(' => ')', '{' => '}', '[' => ']',
        '"' => '"', "'" => "'",
        '//' => "\n", '#' => "\n", '/*' => '*/',
        '<<<' => '_heredoc_special_case_',
    ];

    public function __construct()
    {
        $this->initials = '/^(' . implode('|', array_map([$this, 'quote'], array_keys($this->pairs))) . ')/';
    }

    /**
     * Break the $buffer into chunks, with one for each highest-level construct possible.
     * If the buffer is incomplete, returns an empty array.
     *
     * @param string $buffer
     *
     * @return array
     */
    public function statements($buffer)
    {
        $result = $this->create_result($buffer);

        while (strlen($result->buffer) > 0) {
            $this->reset_result($result);

            if ($result->state == '<<<') {
                if (!$this->heredoc_start($result)) {
                    continue;
                }
            }

            $rules = ['scan_use', 'scan_esc_char', 'scan_region', 'scan_state_entrant', 'scan_wsp', 'scan_char'];

            foreach ($rules as $method) {
                if ($this->$method($result)) {
                    break;
                }
            }

            if ($result->stop) {
                break;
            }
        }

        if (!empty($result->statements) && trim($result->stmt) === '' && strlen($result->buffer) == 0) {
            $this->combine_statements($result);
            $this->debug($result);

            return $result->statements;
        }
    }

    public function quote($token)
    {
        return preg_quote($token, '/');
    }

    private function create_result($buffer)
    {
        $result = new \stdClass();
        $result->buffer = $buffer;
        $result->stmt = '';
        $result->state = null;
        $result->states = [];
        $result->statements = [];
        $result->stop = false;
        return $result;
    }

    private function reset_result($result)
    {
        $result->stop = false;
        $result->state = end($result->states);
        $result->terminator = $result->state ? '/^(.*?' . preg_quote($this->pairs[$result->state], '/') . ')/s' : null;
    }

    private function combine_statements($result)
    {
        $combined = [];

        foreach ($result->statements as $scope) {
            if (trim($scope) === ';' || substr(trim($scope), -1) !== ';') {
                $combined[] = ((string) array_pop($combined)) . $scope;
            } else {
                $combined[] = $scope;
            }
        }

        $result->statements = $combined;
    }

    private function debug($result)
    {
        $result->statements[] = $this->prepare_debug(array_pop($result->statements));
    }

    private function heredoc_start($result)
    {
        if (preg_match('/^([\'"]?)([a-z_][a-z0-9_]*)\\1/i', $result->buffer, $match)) {
            $docId = $match[2];
            $result->stmt .= $match[0];
            $result->buffer = substr($result->buffer, strlen($match[0]));
            $result->terminator = '/^(.*?\n' . $docId . ');?\n/s';
            return true;
        }

        return false;
    }

    private function scan_wsp($result)
    {
        if (preg_match('/^\s+/', $result->buffer, $match)) {
            if (!empty($result->statements) && $result->stmt === '') {
                $result->statements[] = array_pop($result->statements) . $match[0];
            } else {
                $result->stmt .= $match[0];
            }

            $result->buffer = substr($result->buffer, strlen($match[0]));
            return true;
        }

        return false;
    }

    private function scan_esc_char($result)
    {
        if (($result->state === '"' || $result->state === "'") && preg_match('/^[^' . $result->state . ']*?\\\\./s', $result->buffer, $match)) {
            $result->stmt .= $match[0];
            $result->buffer = substr($result->buffer, strlen($match[0]));
            return true;
        }

        return false;
    }

    private function scan_region($result)
    {
        if (in_array($result->state, ['"', "'", '<<<', '//', '#', '/*'])) {
            if (preg_match($result->terminator, $result->buffer, $match)) {
                $result->stmt .= $match[1];
                $result->buffer = substr($result->buffer, strlen($match[1]));
                array_pop($result->states);
            } else {
                $result->stop = true;
            }

            return true;
        }

        return false;
    }

    private function scan_state_entrant($result)
    {
        if (preg_match($this->initials, $result->buffer, $match)) {
            $result->stmt .= $match[0];
            $result->buffer   = substr($result->buffer, strlen($match[0]));
            $result->states[] = $match[0];
            return true;
        }

        return false;
    }

    private function scan_char($result)
    {
        $chr = substr($result->buffer, 0, 1);
        $result->stmt .= $chr;
        $result->buffer = substr($result->buffer, 1);

        if ($result->state && $chr == $this->pairs[$result->state]) {
            array_pop($result->states);
        }

        if (empty($result->states) && ($chr == ';' || $chr == '}')) {
            if (!$this->is_lambda($result->stmt) || $chr == ';') {
                $result->statements[] = $result->stmt;
                $result->stmt         = '';
            }
        }

        return true;
    }

    private function scan_use($result)
    {
        if (preg_match("/^use (.+?);/", $result->buffer, $use)) {
            $result->buffer = substr($result->buffer, strlen($use[0]));

            if (strpos($use[0], ' as ') !== false) {
                list($class, $alias) = explode(' as ', $use[1]);
            } else {
                $class = $use[1];
                $alias = substr($use[1], strrpos($use[1], '\\') + 1);
            }

            $result->statements[] = sprintf("class_alias('%s', '%s');", $class, $alias);
            return true;
        }

        return false;
    }

    private function is_lambda($input)
    {
        return preg_match('/^([^=]*?=\s*)?function\s*\([^\)]*\)\s*(use\s*\([^\)]*\)\s*)?\s*\{.*\}\s*;?$/is', trim($input));
    }

    private function is_returnable($input)
    {
        $input = trim($input);

        if (substr($input, -1) == ';' && substr($input, 0, 1) != '{') {
            $returnables = [
                'echo', 'print', 'exit', 'die', 'goto', 'global', 'include', 'include_once', 'require',
                'require_once', 'list', 'return', 'do', 'for', 'foreach', 'while', 'if', 'function',
                'namespace', 'class', 'interface', 'abstract', 'switch', 'declare', 'throw', 'try', 'unset',
            ];
            return $this->is_lambda($input) || !preg_match('/^(' . implode('|', $returnables) . ')\b/i', $input);
        }

        return false;
    }

    private function prepare_debug($input)
    {
        if ($this->is_returnable($input) && !preg_match('/^\s*return/i', $input)) {
            $input = sprintf('return %s', $input);
        }

        return $input;
    }
}
