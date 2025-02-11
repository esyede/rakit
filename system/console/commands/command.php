<?php

namespace System\Console\Commands;

use System\Console\Color;

defined('DS') or exit('No direct access.');

abstract class Command
{
    private function supported()
    {
        if (DS === '\\') {
            return (function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT))
                || (getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON');
        }
        return function_exists('posix_isatty') && @posix_isatty(STDOUT);
    }

    protected function info($text, $newline = true)
    {
        $text = $this->supported() ? "\033[32m{$text}\033[m" : $text;
        return $text . ($newline ? PHP_EOL : '');
    }

    protected function warning($text, $newline = true)
    {
        $text = $this->supported() ? "\033[33m{$text}\033[m" : $text;
        return $text . ($newline ? PHP_EOL : '');
    }

    protected function error($text, $newline = true)
    {
        $text = $this->supported() ? "\033[31m{$text}\033[m" : $text;
        return $text . ($newline ? PHP_EOL : '');
    }
}
