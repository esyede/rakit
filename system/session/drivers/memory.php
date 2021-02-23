<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct script access.');

class Memory extends Driver
{
    /**
     * Berisi data payload session yang akan direturn oleh driver.
     *
     * @var array
     */
    public $session;

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
        return $this->session;
    }

    /**
     * Simpan session.
     *
     * @param array $session
     * @param array $config
     * @param bool  $exists
     */
    public function save($session, $config, $exists)
    {
        // ..
    }

    /**
     * Hapus session berdasarkan ID yang diberikan.
     *
     * @param string $id
     */
    public function delete($id)
    {
        // ..
    }
}
