<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

/**
 * Penyimpanan riwayat debugbar berbasis file (mirip FileStorage php-debugbar).
 * Tiap request disimpan sebagai satu berkas JSON di folder history, lalu
 * berkas terlama otomatis dipangkas agar jumlahnya tak melebihi batas.
 *
 * Kompatibel PHP 5.4 s/d 8.5 (hanya memakai fungsi inti: json_*, glob,
 * filemtime, dan array standar).
 */
class Storage
{
    /** @var string */
    protected $dir;

    /** @var int */
    protected $max;

    /**
     * @param string $dir
     * @param int    $max
     */
    public function __construct($dir, $max = 25)
    {
        $this->dir = rtrim($dir, '/\\');
        $this->max = (int) $max > 0 ? (int) $max : 25;
    }

    /**
     * Simpan payload satu request. $data minimal berisi:
     *   ['content' => (string), 'dumps' => (array), 'meta' => (array)]
     *
     * @param string $id
     * @param array  $data
     *
     * @return bool
     */
    public function save($id, array $data)
    {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0755, true);
        }

        if (!is_dir($this->dir) || !is_writable($this->dir)) {
            return false;
        }

        $file = $this->path($id);
        $json = json_encode($data);

        if (false === $json) {
            return false;
        }

        $ok = @file_put_contents($file, $json, LOCK_EX);
        $this->cleanup();

        return false !== $ok;
    }

    /**
     * Ambil payload satu request berdasarkan ID.
     *
     * @param string $id
     *
     * @return array|null
     */
    public function get($id)
    {
        $file = $this->path($id);

        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? $data : null;
    }

    /**
     * Daftar metadata request terbaru (urut terbaru dulu). Setiap entri
     * adalah meta yang tersimpan ditambah kunci 'id'.
     *
     * @param int $limit
     *
     * @return array
     */
    public function recent($limit = 20)
    {
        $files = $this->files();

        if (!$files) {
            return array();
        }

        $metas = array();

        foreach ($files as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            $meta = (is_array($data) && isset($data['meta']) && is_array($data['meta']))
                ? $data['meta'] : array();
            $meta['id'] = basename($file, '.json');
            $meta['ts'] = isset($meta['ts']) ? (float) $meta['ts'] : (float) filemtime($file);
            $metas[] = $meta;
        }

        usort($metas, function ($a, $b) {
            if ($a['ts'] == $b['ts']) {
                return 0;
            }
            return ($a['ts'] < $b['ts']) ? 1 : -1;
        });

        return array_slice($metas, 0, (int) $limit > 0 ? (int) $limit : 20);
    }

    /**
     * Buang berkas terlama bila jumlahnya melewati batas.
     *
     * @return void
     */
    protected function cleanup()
    {
        $files = $this->files();

        if (!$files || count($files) <= $this->max) {
            return;
        }

        // Urutkan berdasar timestamp presisi (meta 'ts') — bukan mtime berkas
        // yang granularitasnya 1 detik, sehingga beberapa request pada detik
        // yang sama tetap terurut benar saat dipangkas.
        $times = $this->timestamps();
        asort($times); // terlama dulu

        $remove = count($files) - $this->max;
        foreach ($times as $file => $ts) {
            if ($remove-- <= 0) {
                break;
            }
            @unlink($file);
        }
    }

    /**
     * Peta [berkas => timestamp] memakai meta 'ts' (fallback mtime berkas).
     *
     * @return array
     */
    protected function timestamps()
    {
        $out = array();

        foreach ($this->files() as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            $out[$file] = (is_array($data) && isset($data['meta']['ts']))
                ? (float) $data['meta']['ts']
                : (float) filemtime($file);
        }

        return $out;
    }

    /**
     * @return array
     */
    protected function files()
    {
        $files = glob($this->dir . DIRECTORY_SEPARATOR . '*.json');

        return is_array($files) ? $files : array();
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function path($id)
    {
        $id = preg_replace('#[^a-zA-Z0-9_-]#', '', (string) $id);

        return $this->dir . DIRECTORY_SEPARATOR . $id . '.json';
    }
}
