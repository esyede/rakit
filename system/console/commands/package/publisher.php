<?php

namespace System\Console\Commands\Package;

defined('DS') or die('No direct script access.');

use System\File;
use System\Package;

class Publisher
{
    /**
     * Salin aset milik paket ke direktori root 'assets/'.
     *
     * @param string $package
     *
     * @return void
     */
    public function publish($package)
    {
        if (! Package::exists($package)) {
            echo 'Package is not registered: '.$package;

            return;
        }

        $path = Package::path($package);

        $this->move($path.'assets', path('assets').'packages'.DS.$package);

        echo 'Assets published for package: '.$package.PHP_EOL;
    }

    /**
     * Hapus aset milik paket dari direktori root 'assets/'.
     *
     * @param string $package
     *
     * @return void
     */
    public function unpublish($package)
    {
        if (! Package::exists($package)) {
            echo 'Package is not registered: '.$package;

            return;
        }

        File::rmdir(path('assets').'packages'.DS.$package);

        echo 'Assets deleted for package: '.$package.PHP_EOL;
    }

    /**
     * Salin aset milik paket.
     *
     * @param string $source
     * @param string $destination
     *
     * @return void
     */
    protected function move($source, $destination)
    {
        File::cpdir($source, $destination);
    }

    /**
     * Ambil lokasi tujuan aset paket.
     *
     * @param string $package
     *
     * @return string
     */
    protected function to($package)
    {
        return path('assets').'packages'.DS.$package.DS;
    }

    /**
     * Ambil lokasi asal aset paket.
     *
     * @param string $package
     *
     * @return string
     */
    protected function from($package)
    {
        return Package::path($package).'assets';
    }
}
