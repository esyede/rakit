<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct script access.');

use System\Config;
use System\Database\Connection;

class Database extends Driver implements Sweeper
{
    /**
     * Berisi resource koneksi database.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Buat instance baru driver session database.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Muat session berdasarkan ID yang diberikan.
     * Jika session tidak ditemukan, NULL akan direturn.
     *
     * @param string $id
     *
     * @return array
     */
    public function load($id)
    {
        $session = $this->table()->find($id);

        if (! is_null($session)) {
            return [
                'id' => $session->id,
                'last_activity' => $session->last_activity,
                'data' => unserialize($session->data),
            ];
        }
    }

    /**
     * Simpan session.
     *
     * @param array $session
     * @param array $config
     * @param bool  $exists
     */
    public function save(array $session, array $config, $exists)
    {
        if ($exists) {
            $this->table()->where('id', '=', $session['id'])->update([
                'last_activity' => $session['last_activity'],
                'data' => serialize($session['data']),
            ]);
        } else {
            $this->table()->insert([
                'id' => $session['id'],
                'last_activity' => $session['last_activity'],
                'data' => serialize($session['data']),
            ]);
        }
    }

    /**
     * Hapus session berdasarkan ID yang diberikan.
     *
     * @param string $id
     */
    public function delete($id)
    {
        $this->table()->delete($id);
    }

    /**
     * Hapus seluruh session yang telah kedaluwarsa.
     *
     * @param int $expiration
     */
    public function sweep($expiration)
    {
        $this->table()->where('last_activity', '<', $expiration)->delete();
    }

    /**
     * Ambil object query builder untuk tabel session.
     *
     * @return Query
     */
    private function table()
    {
        return $this->connection->table(Config::get('session.table'));
    }
}
