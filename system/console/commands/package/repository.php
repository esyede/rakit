<?php

namespace System\Console\Commands\Package;

defined('DS') or exit('No direct script access.');

use System\Storage;

class Repository
{
    /**
     * Konstruktor.
     */
    public function __construct()
    {
        // ..
    }

    /**
     * Cari data paket di repositori.
     *
     * @param string $name
     *
     * @return array
     */
    public function search($name)
    {
        $packages = $this->packages();
        $total = count($packages);

        for ($i = 0; $i < $total; $i++) {
            if ($name === $packages[$i]['name']) {
                return $packages[$i];
            }
        }

        throw new \Exception(PHP_EOL.sprintf(
            'Error: Package canot be found on the repository: %s', $name
        ).PHP_EOL);
    }

    /**
     * Ambil data seluruh paket yang ada di repositori.
     *
     * @param string $name
     *
     * @return array
     */
    protected function packages()
    {
        $target = path('base').'repositories.json';

        if (! is_file($target)) {
            throw new \Exception('Missing repository. Please contact rakit team.'.PHP_EOL);
        }

        $target = Storage::get($target);

        // Lihat: https://www.php.net/manual/en/function.json-last-error.php#118165
        $packages = @json_decode($target);

        if (strlen(trim($target)) < 1 || JSON_ERROR_NONE !== json_last_error()) {
            throw new \Exception('Broken repository. Please contact rakit team.');
        }

        return json_decode(json_encode($packages), true);
    }
}
