<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct script access.');

use System\Str;
use System\File as Storage;

class File extends Driver implements Sweeper
{
    /**
     * Path tempat menyimpan file session.
     *
     * @var string
     */
    private $path;

    /**
     * Create a new File session driver instance.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
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
        $path = $this->path.$this->naming($id);

        if (is_file($path)) {
            $path = Storage::get($path);
            return unserialize($this->unguard($path));
        }
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
        $path = $this->path.$this->naming($session['id']);
        $session = $this->guard(serialize($session));
        Storage::put($path, $session, LOCK_EX);
    }

    /**
     * Hapus session berdasarkan ID yang diberikan.
     *
     * @param string $id
     */
    public function delete($id)
    {
        $path = $this->path.$this->naming($id);
        Storage::delete($path);
    }

    /**
     * Hapus seluruh session yang telah kedaluwarsa.
     *
     * @param int $expiration
     */
    public function sweep($expiration)
    {
        $files = glob($this->path.'*.session.php');

        if (false === $files || ! is_array($files) || 0 === count($files)) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file) && Storage::modified($file) < $expiration) {
                Storage::delete($file);
            }
        }
    }

    /**
     * Helper untuk format nama file session.
     *
     * @param string $id
     *
     * @return string
     */
    protected function naming($id)
    {
        return crc32((string) $id).'.session.php';
    }

    /**
     * Helper untuk proteksi akses file via browser.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function guard($value)
    {
        $value = (string) $value;
        $guard = "<?php defined('DS') or exit('No direct script access.');?>";

        return $guard.$value;
    }

    /**
     * Helper untuk buang proteksi akses file via browser.
     * (Kebalikan dari method guard).
     *
     * @param string $value
     *
     * @return string
     */
    protected static function unguard($value)
    {
        $value = (string) $value;
        $guard = "<?php defined('DS') or exit('No direct script access.');?>";
        $value = Str::replace_first($guard, '', $value);

        return $value;
    }
}
