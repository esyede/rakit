<?php

namespace System\Console\Commands\Package;

defined('DS') or die('No direct access.');

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
        if (!Package::exists($package)) {
            echo 'Package is not registered: ' . $package;
            return;
        }

        $source = path('package') . $package . DS . 'assets';
        $destination = path('assets') . 'packages' . DS . $package;

        if (!is_dir($source)) {
            echo $this->error('Package does not caontains any assets!');
            return;
        }

        if (is_dir($destination)) {
            echo $this->error('Package assets already published!');
            return;
        }

        Storage::cpdir($source, $destination);

        echo $this->info('Assets published for package: ' . $package);
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
        if (is_dir($destination = path('assets') . 'packages' . DS . $package)) {
            Storage::rmdir($destination);
        }

        echo $this->info('Assets deleted for package: ' . $package);
    }
}
