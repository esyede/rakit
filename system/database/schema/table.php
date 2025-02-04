<?php

namespace System\Database\Schema;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Magic;
use System\Config;

class Table
{
    /**
     * Berisi nama tabel database.
     *
     * @var string
     */
    public $name;

    /**
     * Berisi koneksi database default yang harus digunakan oleh tabel.
     *
     * @var string
     */
    public $connection;

    /**
     * Berisi engine database yang harus digunakan oleh tabel.
     *
     * @var string
     */
    public $engine;

    /**
     * Berisi charset yang harus digunakan oleh tabel.
     *
     * @var string
     */
    public $charset;

    /**
     * Berisi collation yang harus digunakan oleh tabel.
     *
     * @var string
     */
    public $collation;

    /**
     * Berisi list kolom yang harus ditambahkan ke tabel.
     *
     * @var array
     */
    public $columns = [];

    /**
     * Berisi list command yang harus dieksekusi terhadap tabel.
     *
     * @var array
     */
    public $commands = [];

    /**
     * Buat instance schema table baru.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->charset = Config::get('database.connections.mysql.charset');
    }

    /**
     * Penanda bahwa tabel harus dibuat.
     *
     * @return Magic
     */
    public function create()
    {
        return $this->command('create');
    }

    /**
     * Buat primary key pada tabel.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function primary($columns, $name = null)
    {
        return $this->key('primary', $columns, $name);
    }

    /**
     * Set charset untuk tabel.
     *
     * @param string $charset
     */
    public function charset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * Set collation untuk tabel.
     *
     * @param string $collation
     */
    public function collate($collation)
    {
        $this->collation = $collation;
    }

    /**
     * Buat unique index pada tabel.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function unique($columns, $name = null)
    {
        return $this->key('unique', $columns, $name);
    }

    /**
     * Buat full-text index pada tabel.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function fulltext($columns, $name = null)
    {
        return $this->key('fulltext', $columns, $name);
    }

    /**
     * Buat index pada tabel.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function index($columns, $name = null)
    {
        return $this->key('index', $columns, $name);
    }

    /**
     * Buat foreign key constraint pada tabel.
     *
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function foreign($columns, $name = null)
    {
        return $this->key('foreign', $columns, $name);
    }

    /**
     * Buat command untuk pembuatan index.
     *
     * @param string       $type
     * @param string|array $columns
     * @param string       $name
     *
     * @return Magic
     */
    public function key($type, $columns, $name)
    {
        $columns = is_array($columns) ? $columns : [$columns];

        if (is_null($name)) {
            $name = str_replace(['-', '.'], '_', $this->name);
            $name = $name . '_' . implode('_', $columns) . '_' . $type;
        }

        return $this->command($type, compact('name', 'columns'));
    }

    /**
     * Rename tabel database.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function rename($name)
    {
        return $this->command('rename', compact('name'));
    }

    /**
     * Hapus tabel database.
     *
     * @return Magic
     */
    public function drop()
    {
        return $this->command('drop');
    }

    /**
     * Hapus kolom dari database.
     *
     * @param string|array $columns
     */
    public function drop_column($columns)
    {
        $columns = is_array($columns) ? $columns : [$columns];
        return $this->command('drop_column', compact('columns'));
    }

    /**
     * Hapus primary key dari tabel.
     *
     * @param string $name
     */
    public function drop_primary($name = null)
    {
        return $this->drop_key('drop_primary', $name);
    }

    /**
     * Hapus unique index dari tabel.
     *
     * @param string $name
     */
    public function drop_unique($name)
    {
        return $this->drop_key('drop_unique', $name);
    }

    /**
     * Hapus full-text index dari tabel.
     *
     * @param string $name
     */
    public function drop_fulltext($name)
    {
        return $this->drop_key('drop_fulltext', $name);
    }

    /**
     * Hapus index dari tabel.
     *
     * @param string $name
     */
    public function drop_index($name)
    {
        return $this->drop_key('drop_index', $name);
    }

    /**
     * Hapus foreign key constraint dari tabel.
     *
     * @param string $name
     */
    public function drop_foreign($name)
    {
        return $this->drop_key('drop_foreign', $name);
    }

    /**
     * Buat command penghapusan index.
     *
     * @param string $type
     * @param string $name
     *
     * @return Magic
     */
    protected function drop_key($type, $name)
    {
        return $this->command($type, compact('name'));
    }

    /**
     * Tambahkan kolom auto-increment ke tabel.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function increments($name)
    {
        return $this->integer($name, true);
    }

    /**
     * Tambahkan kolom string ke tabel.
     *
     * @param string $name
     * @param int    $length
     *
     * @return Magic
     */
    public function string($name, $length = 200)
    {
        return $this->column('string', compact('name', 'length'));
    }

    /**
     * Tambahkan kolom integer ke tabel.
     *
     * @param string $name
     * @param bool   $increment
     *
     * @return Magic
     */
    public function integer($name, $increment = false)
    {
        return $this->column('integer', compact('name', 'increment'));
    }

    /**
     * Tambahkan kolom big integer ke tabel.
     *
     * @param string $name
     * @param bool   $increment
     *
     * @return Magic
     */
    public function biginteger($name, $increment = false)
    {
        return $this->column('biginteger', compact('name', 'increment'));
    }

    /**
     * Tambahkan kolom float ke tabel.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function float($name)
    {
        return $this->column('float', compact('name'));
    }

    /**
     * Tambahkan kolom desimal ke tabel.
     *
     * @param string $name
     * @param int    $precision
     * @param int    $scale
     *
     * @return Magic
     */
    public function enum($name, array $allowed)
    {
        return $this->column('enum', compact('name', 'allowed'));
    }

    /**
     * Tambahkan kolom enum ke tabel.
     *
     * @param string $name
     * @param int    $precision
     * @param int    $scale
     *
     * @return Magic
     */
    public function decimal($name, $precision, $scale)
    {
        return $this->column('decimal', compact('name', 'precision', 'scale'));
    }

    /**
     * Tambahkan kolom boolean ke tabel.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function boolean($name)
    {
        return $this->column('boolean', compact('name'));
    }

    /**
     * Buat kolom datetime created_at dan updated_at.
     */
    public function timestamps()
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    /**
     * Tambahkan kolom datetime ke tabel.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function date($name)
    {
        return $this->column('date', compact('name'));
    }

    /**
     * Tambahkan kolom timestamp ke tabel.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function timestamp($name)
    {
        return $this->column('timestamp', compact('name'));
    }

    /**
     * Tambahkan kolom text ke tabel.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function text($name)
    {
        return $this->column('text', compact('name'));
    }

    /**
     * Tambahkan kolom longtext ke tabel.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function longtext($name)
    {
        return $this->column('longtext', compact('name'));
    }

    /**
     * Tambahkan kolom blob ke tabel.
     *
     * @param string $name
     *
     * @return Magic
     */
    public function blob($name)
    {
        return $this->column('blob', compact('name'));
    }

    /**
     * Set koneksi database untuk operasi tabel.
     *
     * @param string $connection
     */
    public function on($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Cek apakah schema memiliki command 'create'.
     *
     * @return bool
     */
    public function creating()
    {
        return !is_null(Arr::first($this->commands, function ($key, $value) {
            return 'create' === $value->type;
        }));
    }

    /**
     * Buat instance command baru.
     *
     * @param string $type
     * @param array  $parameters
     *
     * @return Magic
     */
    protected function command($type, array $parameters = [])
    {
        return $this->commands[] = new Magic(array_merge(compact('type'), $parameters));
    }

    /**
     * Buat instance column baru.
     *
     * @param string $type
     * @param array  $parameters
     *
     * @return Magic
     */
    protected function column($type, array $parameters = [])
    {
        return $this->columns[] = new Magic(array_merge(compact('type'), $parameters));
    }
}
