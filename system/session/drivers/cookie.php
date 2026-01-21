<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

use System\Crypter;
use System\Cookie as BaseCookie;

class Cookie extends Driver
{
    /**
     * Nama cookie untuk menyimpan payoad session.
     *
     * @var string
     */
    const PAYLOAD = 'session_payload';

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
        if (BaseCookie::has(Cookie::PAYLOAD)) {
            return unserialize(Crypter::decrypt(BaseCookie::get(Cookie::PAYLOAD)));
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
        BaseCookie::put(
            Cookie::PAYLOAD,
            Crypter::encrypt(serialize($session)),
            $config['lifetime'],
            $config['path'],
            $config['domain'],
            $config['secure'],
            $config['samesite']
        );
    }

    /**
     * Hapus session berdasarkan ID yang diberikan.
     *
     * @param string $id
     */
    public function delete($id)
    {
        BaseCookie::forget(Cookie::PAYLOAD);
    }
}
