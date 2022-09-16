<?php

namespace System\Console\Commands\Package;

defined('DS') or die('No direct script access.');

use System\Storage;
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

        Storage::cpdir(path('package').$package, path('assets').'packages'.DS.$package);

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
            echo 'Package is not registered: '.$package.PHP_EOL;
            return;
        }

        if (is_dir($destination = path('assets').'packages'.DS.$package)) {
            Storage::rmdir($destination);
        }

        echo 'Assets deleted for package: '.$package.PHP_EOL;
    }
}
