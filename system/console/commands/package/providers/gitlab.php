<?php

namespace System\Console\Commands\Package\Providers;

defined('DS') or exit('No direct script access.');

class Gitlab extends Provider
{
    protected $zipball = '<repository>/repository/archive.zip?ref=<version>';

    /**
     * Instal paket yang diberikan.
     *
     * @param array  $package
     * @param string $path
     *
     * @return void
     */
    public function install(array $package, $path)
    {
        $repository = $package['repository'];
        $compatible = isset($package['compatibilities']['v' . RAKIT_VERSION])
            ? $package['compatibilities']['v' . RAKIT_VERSION]
            : null;

        if (!$compatible) {
            throw new \Exception(PHP_EOL . sprintf(
                'Error: No compatible package for your rakit version (v%s)',
                RAKIT_VERSION
            ) . PHP_EOL);
        }

        $url = str_replace(['<repository>', '<version>'], [$repository, $compatible], $this->zipball);
        parent::zipball($url, $package, $path);
    }
}
