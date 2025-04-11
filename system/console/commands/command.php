<?php

namespace System\Console\Commands;

use System\Console\Color;

defined('DS') or exit('No direct access.');

abstract class Command
{
    /**
     * Tampilkan pesan informasi.
     *
     * @param string $text
     * @param bool   $newline
     *
     * @return string
     */
    protected function info($text, $newline = true)
    {
        return Color::green($text, $newline);
    }

    /**
     * Tampilkan pesan peringatan.
     *
     * @param string $text
     * @param bool   $newline
     *
     * @return string
     */
    protected function warning($text, $newline = true)
    {
        return Color::yellow($text, $newline);
    }

    /**
     * Tampilkan pesan error.
     *
     * @param string $text
     * @param bool   $newline
     *
     * @return string
     */
    protected function error($text, $newline = true)
    {
        return Color::red($text, $newline);
    }

    /**
     * Tampilkan progress bar.
     *
     * @param int $current_percentage
     *
     * @return string
     */
    protected function progress($current_percentage)
    {
        $current_percentage = intval($current_percentage);

        if ($current_percentage > 100) {
            throw new \Exception('Current progress percentage should not be greater than 100');
        }

        $done = floor((10 * floor(($current_percentage * 100) / 100)) / 100);
        return $this->info(sprintf('%s%s', str_repeat('▓', $done), str_repeat('▓', 10 - $done)), false);
    }
}
