<?php

namespace System\Database;

defined('DS') or exit('No direct script access.');

abstract class Grammar
{
    /**
     * Berisi keyword identifier untuk sistem database tertentu.
     *
     * @var string
     */
    protected $wrapper = '"%s"';

    /**
     * Berisi instance koneksi database untuk grammar saat ini.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Buat instance database grammar baru.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Bungkus tabel dalam keyword identifier.
     *
     * @param string $table
     *
     * @return string
     */
    public function wrap_table($table)
    {
        if ($table instanceof Expression) {
            return $this->wrap($table);
        }

        $prefix = '';

        if (isset($this->connection->config['prefix'])) {
            $prefix = $this->connection->config['prefix'];
        }

        return $this->wrap($prefix . $table);
    }

    /**
     * Bungkus vlue dalam keyword identifier.
     *
     * @param string $value
     *
     * @return string
     */
    public function wrap($value)
    {
        if ($value instanceof Expression) {
            return $value->get();
        }

        if (false !== stripos((string) $value, ' as ')) {
            $segments = explode(' ', $value);
            return sprintf('%s AS %s', $this->wrap($segments[0]), $this->wrap($segments[2]));
        }

        $segments = explode('.', $value);
        $wrapped = [];

        foreach ($segments as $key => $value) {
            if (0 === $key && count($segments) > 1) {
                $wrapped[] = $this->wrap_table($value);
            } else {
                $wrapped[] = $this->wrap_value($value);
            }
        }

        return implode('.', $wrapped);
    }

    /**
     * Bungkus sebuah string value dalam keyword identifier.
     *
     * @param string $value
     *
     * @return string
     */
    protected function wrap_value($value)
    {
        return ('*' === $value) ? '*' : sprintf($this->wrapper, $value);
    }

    /**
     * Buat parameter query dari sebuah array.
     *
     * <code>
     *
     *      // Mereturn '?, ?, ?', yang nantinya bisa digunakan untuk place-holder
     *      $parameters = $grammar->parameterize([1, 2, 3]);
     *
     *      // Mereturn '?, "Budi"' karena ada raw query yang digunakan
     *      $parameters = $grammar->parameterize([1, DB::raw('Budi')]);
     *
     * </code>
     *
     * @param array $values
     *
     * @return string
     */
    final public function parameterize(array $values)
    {
        return implode(', ', array_map([$this, 'parameter'], $values));
    }

    /**
     * Ambil string parameter query yang sesuai untuk sebuah value.
     *
     * <code>
     *
     *      // Mereturn sebuah '?' untuk place-holder
     *      $value = $grammar->parameter('Budi Purnomo');
     *
     *      // Mereturn 'Budi Purnomo' karena ada raw query yang digunakan
     *      $value = $grammar->parameter(DB::raw('Budi Purnomo'));
     *
     * </code>
     *
     * @param mixed $value
     *
     * @return string
     */
    final public function parameter($value)
    {
        return ($value instanceof Expression) ? $value->get() : '?';
    }

    /**
     * Buat list nama kolom yang dibungkus dan dipisahkan dengan koma.
     *
     * <code>
     *
     *      // Mereturn '"Budi", "Purnomo"' ketika identifiernya berupa tanda kutip
     *      $columns = $grammar->columnize(['Budi', 'Purnomo']);
     *
     * </code>
     *
     * @param array $columns
     *
     * @return string
     */
    final public function columnize(array $columns)
    {
        return implode(', ', array_map([$this, 'wrap'], $columns));
    }
}
