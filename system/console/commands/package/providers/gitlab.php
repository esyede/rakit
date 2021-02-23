<?php

namespace System\Console\Commands\Package\Providers;

defined('DS') or exit('No direct script access.');

class Gitlab extends Provider
{
    protected $zipball = '<repository>/repository/archive.zip?ref=<version>';

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
            $exception = 'Error: No compatible package for your rakit version (%s)';
            throw new \Exception(PHP_EOL.sprintf($exception, RAKIT_VERSION).PHP_EOL);
        }

        $zipball_url = str_replace(
            ['<repository>', '<version>'],
            [$repository, $compatible],
            $this->zipball
        );

        parent::zipball($zipball_url, $package, $path);
    }
}
