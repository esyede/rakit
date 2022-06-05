<?php
namespace System\Console;

defined('DS') or exit('No direct script access.');

class Table
{
    const HEADER_INDEX = -1;
    const HORIZONTAL_ROW = 'HR';

    protected $data = [];
    protected $border = true;
    protected $all_borders = false;
    protected $padding = 1;
    protected $indent = 0;

    private $row_index = -1;
    private $column_widths = [];

    /**
     * Tambahkan header tabel.
     *
     * @param string $content
     */
    public function add_header($content = '')
    {
        $this->data[self::HEADER_INDEX][] = $content;
        return $this;
    }

    /**
     * Alias untuk add_header.
     *
     * @param array $content
     */
    public function set_headers(array $content)
    {
        $this->data[self::HEADER_INDEX] = $content;
        return $this;
    }

    /**
     * Ambil header tabel.
     *
     * @return array
     */
    public function get_headers()
    {
        return isset($this->data[self::HEADER_INDEX]) ? $this->data[self::HEADER_INDEX] : null;
    }

    /**
     * Tambahkan baris.
     *
     * @param array|null $data
     */
    public function add_row(array $data = null)
    {
        $this->row_index++;

        if (is_array($data)) {
            foreach ($data as $col => $content) {
                $this->data[$this->row_index][$col] = $content;
            }
        }

        return $this;
    }

    /**
     * Tambahkan kolom.
     *
     * @param array $content
     * @param int   $column
     * @param int   $row
     */
    public function add_column($content, $column = null, $row = null)
    {
        $row = is_null($row) ? $this->row_index : $row;

        if (is_null($column)) {
            $column = isset($this->data[$row]) ? count($this->data[$row]) : 0;
        }

        $this->data[$row][$column] = $content;

        return $this;
    }

    /**
     * Tampilkan border tabel?
     */
    public function show_border()
    {
        $this->border = true;
        return $this;
    }

    /**
     * Sembunyukan border tabel?
     */
    public function hide_border()
    {
        $this->border = false;
        return $this;
    }

    /**
     * Tampilkan semua border tabel?
     */
    public function show_borders()
    {
        $this->show_border();
        $this->all_borders = true;

        return $this;
    }

    /**
     * Set padding.
     *
     * @param int $value
     */
    public function set_padding($value = 1)
    {
        $this->padding = $value;
        return $this;
    }

    /**
     * Set indentasi tabel.
     *
     * @param int $value
     */
    public function set_indent($value = 0)
    {
        $this->indent = $value;
        return $this;
    }

    /**
     * Tambah garis border.
     */
    public function add_border_line()
    {
        $this->row_index++;
        $this->data[$this->row_index] = self::HORIZONTAL_ROW;

        return $this;
    }

    /**
     * Cetak/tampilkan hasil tabel.
     */
    public function display()
    {
        echo $this->get_table();
    }

    /**
     * Render tabel.
     *
     * @return string
     */
    public function get_table()
    {
        $this->calculate_column_width();
        $output = $this->border ? $this->get_border_line() : '';

        foreach ($this->data as $y => $row) {
            if (self::HORIZONTAL_ROW === $row) {
                if (! $this->all_borders) {
                    $output .= $this->get_border_line();
                    unset($this->data[$y]);
                }

                continue;
            }

            foreach ($row as $x => $cell) {
                $output .= $this->get_cell_output($x, $row);
            }

            $output .= PHP_EOL;

            if (self::HEADER_INDEX === $y) {
                $output .= $this->get_border_line();
            } else {
                if ($this->all_borders) {
                    $output .= $this->get_border_line();
                }
            }
        }

        if (! $this->all_borders) {
            $output .= $this->border ? $this->get_border_line() : '';
        }

        return is_cli() ? $output : '<pre>'.$output.'</pre>';
    }

    /**
     * Ambil garis border.
     *
     * @return string
     */
    private function get_border_line()
    {
        $output = '';

        if (isset($this->data[0])) {
            $columnCount = count($this->data[0]);
        } elseif (isset($this->data[self::HEADER_INDEX])) {
            $columnCount = count($this->data[self::HEADER_INDEX]);
        } else {
            return $output;
        }

        for ($column = 0; $column < $columnCount; $column++) {
            $output .= $this->get_cell_output($column);
        }

        if ($this->border) {
            $output .= '+';
        }

        return $output.PHP_EOL;
    }

    /**
     * Ambil output sel.
     *
     * @param int $index
     * @param int $row
     *
     * @return string
     */
    private function get_cell_output($index, $row = null)
    {
        $cell = $row ? $row[$index] : '-';
        $width = $this->column_widths[$index];
        $pad = $row ? $width - mb_strlen($cell, 'UTF-8') : $width;
        $padding = str_repeat($row ? ' ' : '-', $this->padding);

        $output = (0 === $index) ? str_repeat(' ', $this->indent) : '';
        $output .= $this->border ? ($row ? '|' : '+') : '';
        $output .= $padding;

        $cell = trim(preg_replace('/\s+/', ' ', $cell));
        $content = preg_replace('#\x1b[[][^A-Za-z]*[A-Za-z]#', '', $cell);
        $delta = mb_strlen($cell, 'UTF-8') - mb_strlen($content, 'UTF-8');

        $output .= $this->strpad($cell, $width + $delta, $row ? ' ' : '-');
        $output .= $padding;
        $output .= ($row && $index === count($row) - 1 && $this->border) ? ($row ? '|' : '+') : '';

        return $output;
    }

    /**
     * Hitung lebar kolom.
     *
     * @return int
     */
    private function calculate_column_width()
    {
        foreach ($this->data as $y => $row) {
            if (is_array($row)) {
                foreach ($row as $x => $col) {
                    $width = mb_strlen(preg_replace('/\x1b[[][^A-Za-z]*[A-Za-z]/', '', $col), 'UTF-8');

                    if (! isset($this->column_widths[$x])) {
                        $this->column_widths[$x] = mb_strlen($width, '8bit');
                    } else {
                        if (strlen($width) > $this->column_widths[$x]) {
                            $this->column_widths[$x] = mb_strlen($width, '8bit');
                        }
                    }
                }
            }
        }

        return $this->column_widths;
    }

    /**
     * Strpad dengan dukungan unicode.
     *
     * @param string $str
     * @param int    $amount
     * @param string $content
     * @param int    $direction
     *
     * @return string
     */
    private function strpad($str, $amount, $content = ' ', $direction = STR_PAD_RIGHT)
    {
        $len = mb_strlen($str, 'UTF-8');
        $padlen = mb_strlen($content, 'UTF-8');

        if (! $len && (STR_PAD_RIGHT === $direction || STR_PAD_LEFT === $direction)) {
            $len = 1;
        }

        if (! $amount || ! $padlen || $amount <= $len) {
            return $str;
        }

        $result = null;
        $repeat = ceil($len - $padlen + $amount);

        if (STR_PAD_RIGHT === $direction) {
            $result = $str.str_repeat($content, $repeat);
            $result = mb_substr($result, 0, $amount, 'UTF-8');
        } elseif (STR_PAD_LEFT === $direction) {
            $result = str_repeat($content, $repeat).$str;
            $result = mb_substr($result, -$amount, null, 'UTF-8');
        } elseif (STR_PAD_BOTH === $direction) {
            $length = ($amount - $len) / 2;
            $repeat = ceil($length / $padlen);
            $result = mb_substr(str_repeat($content, $repeat), 0, floor($length), 'UTF-8').
                $str.mb_substr(str_repeat($content, $repeat), 0, ceil($length), 'UTF-8');
        }

        return $result;
    }
}
