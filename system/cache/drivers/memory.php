<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Arr;

class Memory extends Sectionable
{
    /**
     * Berisi array item cache (temporer).
     *
     * @var string
     */
    public $storage = [];

    /**
     * Cek apakah item ada di cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return !is_null($this->get($key));
    }

    /**
     * Ambil item dari driver cache.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function retrieve($key)
    {
        if ($this->sectionable($key)) {
            list($section, $key) = $this->parse($key);
            return $this->get_from_section($section, $key);
        }

        return Arr::get($this->storage, $key);
    }

    /**
     * Simpan item ke cache untuk beberapa menit.
     *
     * <code>
     *
     *      // Simpan sebuah item ke cache selama 15 menit.
     *      Cache::put('name', 'Budi', 15);
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     */
    public function put($key, $value, $minutes)
    {
        if ($this->sectionable($key)) {
            list($section, $key) = $this->parse($key);
            return $this->put_in_section($section, $key, $value, $minutes);
        }

        Arr::set($this->storage, $key, $value);
    }

    /**
     * Hapus item dari cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        if ($this->sectionable($key)) {
            list($section, $key) = $this->parse($key);

            if ('*' === $key) {
                $this->forget_section($section);
            } else {
                $this->forget_in_section($section, $key);
            }
        } else {
            Arr::forget($this->storage, $key);
        }
    }

    /**
     * Hapus seluruh item cache.
     */
    public function flush()
    {
        $this->storage = [];
    }

    /**
     * Hapus keseluruhan section dari cache.
     *
     * @param string $section
     *
     * @return int|bool
     */
    public function forget_section($section)
    {
        Arr::forget($this->storage, 'section#' . $section);
    }

    /**
     * Ambil nama key item section milik section dan key tertentu.
     *
     * @param string $section
     * @param string $key
     *
     * @return string
     */
    protected function section_item_key($section, $key)
    {
        return 'section#' . $section . '.' . $key;
    }
}
