<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

use System\Cache\Drivers\APC as CacheAPC;

class APC extends Driver
{
    /**
     * Berisi instance driver cache APC.
     *
     * @var System\Cache\Drivers\APC
     */
    private $apc;

    /**
     * Buat instance baru driver session APC.
     *
     * @param System\Cache\Drivers\APC $apc
     */
    public function __construct(CacheAPC $apc)
    {
        $this->apc = $apc;
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
        return $this->apc->get($id);
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
        $this->apc->put($session['id'], $session, $config['lifetime']);
    }

    /**
     * Hapus session berdasarkan ID yang diberikan.
     *
     * @param string $id
     */
    public function delete($id)
    {
        $this->apc->forget($id);
    }
}
