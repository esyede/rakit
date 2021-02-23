<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct script access.');

use System\Str;

abstract class Driver
{
    /**
     * Muat session berdasarkan ID yang diberikan.
     * Jika session tidak ditemukan, NULL akan direturn.
     *
     * @param string $id
     *
     * @return array
     */
    abstract public function load($id);

    /**
     * Simpan session.
     *
     * @param array $session
     * @param array $config
     * @param bool  $exists
     */
    abstract public function save($session, $config, $exists);

    /**
     * Hapus session berdasarkan ID yang diberikan.
     *
     * @param string $id
     */
    abstract public function delete($id);

    /**
     * Buat data session baru dengan ID yang unik.
     *
     * @return array
     */
    public function fresh()
    {
        return ['id' => $this->id(), 'data' => [':new:' => [], ':old:' => []]];
    }

    /**
     * Ambil ID unik yang belum pernah dipakai oleh session.
     *
     * @return string
     */
    public function id()
    {
        if ($this instanceof Cookie) {
            return Str::random(40);
        }

        $session = null;
        $id = null;

        do {
            $id = Str::random(40);
            $session = $this->load($id);
        } while (! is_null($session));

        return $id;
    }
}
