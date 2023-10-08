<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct script access.');

class Metadata
{
    const CREATED = 'c';
    const UPDATED = 'u';
    const LIFETIME = 'l';

    private $name = '__metadata';
    private $storageKey;
    private $lastUsed;

    protected $meta = [];

    /**
     * Konstruktor.
     *
     * @param string $storageKey
     */
    public function __construct($storageKey = '_rakit_meta')
    {
        $this->storageKey = $storageKey;
        $this->meta = [self::CREATED => 0, self::UPDATED => 0, self::LIFETIME => 0];
    }

    public function initialize(array &$array)
    {
        $this->meta = &$array;

        if (isset($array[self::CREATED])) {
            $this->lastUsed = $this->meta[self::UPDATED];
            $this->meta[self::UPDATED] = time();
        } else {
            $this->stampCreated();
        }
    }

    /**
     * Ambil waktu kedaluwarsa cookie session.
     *
     * @return int
     */
    public function getLifetime()
    {
        return $this->meta[self::LIFETIME];
    }

    /**
     * Buat timestamp baru untuk metadata.
     *
     * @param int $lifetime
     */
    public function stampNew($lifetime = null)
    {
        $this->stampCreated($lifetime);
    }

    /**
     * Ambil nama storage key metadata.
     *
     * @return string
     */
    public function getStorageKey()
    {
        return $this->storageKey;
    }

    /**
     * Ambil timestamp waktu pembuatan.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->meta[self::CREATED];
    }

    /**
     * Ambil timestamp waktu penggunaan terakhir.
     *
     * @return int
     */
    public function getLastUsed()
    {
        return $this->lastUsed;
    }

    public function clear()
    {
        // ..
    }

    /**
     * Ambil nama key metadata.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set nama key metadata.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Perbarui timestamp.
     *
     * @param int|null $lifetime
     */
    private function stampCreated($lifetime = null)
    {
        $timeStamp = time();

        $this->meta[self::CREATED] = $timeStamp;
        $this->meta[self::UPDATED] = $timeStamp;
        $this->lastUsed = $timeStamp;

        $this->meta[self::LIFETIME] = (null === $lifetime)
            ? ini_get('session.cookie_lifetime')
            : $lifetime;
    }
}
