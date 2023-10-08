<?php

namespace System\Console\Commands\Package;

defined('DS') or exit('No direct access.');

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

        throw new \Exception(PHP_EOL . sprintf(
            'Error: Package canot be found on the repository: %s',
            $name
        ) . PHP_EOL);
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
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => static::$repository,
            CURLOPT_HTTPGET => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_VERBOSE => get_cli_option('verbose') ? 1 : 0,
            CURLOPT_USERAGENT => sprintf(
                'Mozilla/5.0 (Linux x86_64; rv:%s.0) Gecko/20100101 Firefox/%s.0',
                mt_rand(90, 110),
                mt_rand(90, 110)
            ),
        ]);
        $packages = curl_exec($ch);
        curl_close($ch);

        $packages = json_decode($packages, true);

        if (!is_array($packages) || count($packages) < 1) {
            throw new \Exception('Broken repository data. Please contact rakit team.' . PHP_EOL);
        }

        return $packages;
    }
}
