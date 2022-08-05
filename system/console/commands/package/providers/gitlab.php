<?php

namespace System\Console\Commands\Package\Providers;

defined('DS') or exit('No direct script access.');

class Gitlab extends Provider
{
    protected $zipball = '<repository>/repository/archive.zip?ref=<version>';

    /**
     * Instal paket yang diberikan.
     *
     * @param array $package
     * @param string $path
     *
     * @return void
     */
    public function install(array $package, $path, $version)
    {
        $repository = $package['repository'];
        $this->compatible = isset($package['compatibilities'][RAKIT_VERSION])
            ? $package['compatibilities'][RAKIT_VERSION]
            : null;

        if (! $this->compatible) {
            throw new \Exception(PHP_EOL.sprintf(
                'Error: No compatible package for your rakit version (%s)', RAKIT_VERSION
            ).PHP_EOL);
        }

        $url = str_replace(['<repository>', '<version>'], [$repository, $this->compatible], $this->zipball);
        parent::zipball($url, $package, $path);
    }
}
