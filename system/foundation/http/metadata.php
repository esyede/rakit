<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct access.');

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
     * Constructor.
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
     * Get the lifetime of the metadata.
     *
     * @return int
     */
    public function getLifetime()
    {
        return $this->meta[self::LIFETIME];
    }

    /**
     * Create a new stamp for the metadata.
     *
     * @param int $lifetime
     */
    public function stampNew($lifetime = null)
    {
        $this->stampCreated($lifetime);
    }

    /**
     * Get storage key.
     *
     * @return string
     */
    public function getStorageKey()
    {
        return $this->storageKey;
    }

    /**
     * Get the created timestamp.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->meta[self::CREATED];
    }

    /**
     * Get the last used timestamp.
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
     * Get the metadata key name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the metadata key name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Renew the created and updated timestamps.
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
