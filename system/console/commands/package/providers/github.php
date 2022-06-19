<?php

namespace System\Console\Commands\Package\Providers;

defined('DS') or exit('No direct script access.');

class Github extends Provider
{
    protected $zipball = '<repository>/archive/<version>.zip';

    /**
     * Instal paket yang diberikan.
     *
     * @param string $package
     * @param string $path
     *
     * @return void
     */
    public function install($package, $path)
    {
        $repository = $package['repository'];
        $compatible = isset($package['compatibilities'][RAKIT_VERSION])
            ? $package['compatibilities'][RAKIT_VERSION]
            : false;

        if (! $compatible) {
            throw new \Exception(PHP_EOL.sprintf(
                'Error: No compatible package for your rakit version (%s)', RAKIT_VERSION
            ).PHP_EOL);
        }

        $url = str_replace(['<repository>', '<version>'], [$repository, $compatible], $this->zipball);
        parent::zipball($url, $package, $path);
    }
}
