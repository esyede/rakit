<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct script access.');

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
            $path = file_get_contents($path);
            return $this->unguard(unserialize($path));
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

        file_put_contents($path, $session, LOCK_EX);
    }

    /**
     * Hapus session berdasarkan ID yang diberikan.
     *
     * @param string $id
     */
    public function delete($id)
    {
        $path = $this->path.$this->naming($id);

        if (is_file($path)) {
            unlink($path);
        }
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
            if (is_file($file) && filemtime($file) < $expiration) {
                unlink($file);
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
        return md5((string) $id).'.session.php';
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
        $guard = "<?php defined('DS') or exit('No direct script access.'); ?>";
        $value = $guard.PHP_EOL.PHP_EOL.$value;

        return $value;
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
        $value = ltrim($value, "<?php defined('DS') or exit('No direct script access.'); ?>".PHP_EOL.PHP_EOL);

        return $value;
    }
}
