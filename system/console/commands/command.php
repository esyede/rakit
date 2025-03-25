<?php

namespace System\Console\Commands;

use System\Console\Color;

defined('DS') or exit('No direct access.');

abstract class Command
{
    protected function info($text, $newline = true)
    {
        return Color::green($text, $newline);
    }

    protected function warning($text, $newline = true)
    {
        return Color::yellow($text, $newline);
    }

    protected function error($text, $newline = true)
    {
        return Color::red($text, $newline);
    }
}
