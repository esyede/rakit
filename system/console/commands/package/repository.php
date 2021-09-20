<?php

namespace System\Console\Commands\Package;

defined('DS') or exit('No direct script access.');

use System\Storage;
use System\Curl;

class Repository
{
    /**
     * Target repository.
     *
     * @var string
     */
    public static $repository = 'https://rakit.esyede.my.id/repositories.json';

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
        $response = Curl::get(static::$repository);
        $packages = json_decode(json_encode($response->body), true);

        if (! is_array($packages) || count($packages) < 1) {
            throw new \Exception('Broken repository json data. Please contact rakit team.'.PHP_EOL);
        }

        return $packages;
    }
}
