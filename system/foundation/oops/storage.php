<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

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
     * Save request payload.
     *
     * @param string $id
     * @param array  $data
     *
     * @return bool
     */
    public function save($id, array $data)
    {
        if (! is_dir($this->dir)) {
            @mkdir($this->dir, 0755, true);
        }

        if (! is_dir($this->dir) || ! is_writable($this->dir)) {
            return false;
        }

        $file = $this->path($id);
        $json = json_encode($data);

        if (false === $json) {
            return false;
        }

        $ok = @file_put_contents($file, $json, LOCK_EX);

        if (false !== $ok) {
            $meta = (isset($data['meta']) && is_array($data['meta'])) ? $data['meta'] : [];
            $sidecar = json_encode($meta);

            if (false !== $sidecar) {
                @file_put_contents($this->meta_path($id), $sidecar, LOCK_EX);
            }
        }

        $this->cleanup();

        return false !== $ok;
    }

    /**
     * Get request payload by ID.
     *
     * @param string $id
     *
     * @return array|null
     */
    public function get($id)
    {
        $file = $this->path($id);

        if (! is_file($file)) {
            return;
        }

        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? $data : null;
    }

    /**
     * Get recent metas.
     *
     * @param int $limit
     *
     * @return array
     */
    public function recent($limit = 20)
    {
        $files = $this->files();

        if (! $files) {
            return [];
        }

        $metas = [];

        foreach ($files as $file) {
            $meta = $this->meta_of($file);
            $meta['id'] = basename($file, '.json');
            $meta['ts'] = isset($meta['ts']) ? (float) $meta['ts'] : $this->stamp($file);
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
     * Cleanup old metas.
     *
     * @return void
     */
    protected function cleanup()
    {
        $files = $this->files();

        if (! $files || count($files) <= $this->max) {
            return;
        }

        $times = $this->timestamps($files);
        asort($times); // oldest first

        $remove = count($files) - $this->max;
        foreach ($times as $file => $ts) {
            if ($remove-- <= 0) {
                break;
            }
            @unlink($file);
            @unlink($this->meta_path(basename($file, '.json')));
        }
    }

    /**
     * Get meta timestamps.
     *
     * @param array|null $files
     *
     * @return array
     */
    protected function timestamps($files = null)
    {
        $out = [];
        $files = is_array($files) ? $files : $this->files();

        foreach ($files as $file) {
            $meta = $this->meta_of($file);
            $out[$file] = isset($meta['ts']) ? (float) $meta['ts'] : $this->stamp($file);
        }

        return $out;
    }

    /**
     * Modify meta timestamp.
     *
     * @param string $file
     *
     * @return float
     */
    protected function stamp($file)
    {
        return is_file($file) ? (float) filemtime($file) : 0.0;
    }

    /**
     * Read meta timestamp.
     *
     * @param string $file
     *
     * @return array
     */
    protected function meta_of($file)
    {
        $sidecar = $this->meta_path(basename($file, '.json'));

        if (is_file($sidecar)) {
            $meta = json_decode((string) file_get_contents($sidecar), true);

            if (is_array($meta)) {
                return $meta;
            }
        }

        if (! is_file($file)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($file), true);
        $meta = (is_array($data) && isset($data['meta']) && is_array($data['meta'])) ? $data['meta'] : [];

        $encoded = json_encode($meta);

        if (false !== $encoded) {
            @file_put_contents($sidecar, $encoded, LOCK_EX);
        }

        return $meta;
    }

    /**
     * @return array
     */
    protected function files()
    {
        $files = glob($this->dir.DIRECTORY_SEPARATOR.'*.json');

        return is_array($files) ? $files : [];
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function path($id)
    {
        return $this->dir.DIRECTORY_SEPARATOR.$this->sanitize($id).'.json';
    }

    /**
     * Get meta path..
     *
     * @param string $id
     *
     * @return string
     */
    protected function meta_path($id)
    {
        return $this->dir.DIRECTORY_SEPARATOR.$this->sanitize($id).'.meta';
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function sanitize($id)
    {
        return preg_replace('#[^a-zA-Z0-9_-]#', '', (string) $id);
    }
}
