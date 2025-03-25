<?php

namespace System\Console\Commands\Package;

defined('DS') or die('No direct access.');

use System\Storage;
use System\Package;
use System\Console\Color;

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
            echo Color::red('Package is not registered: ' . $package);
            return;
        }

        $source = path('package') . $package . DS . 'assets';
        $destination = path('assets') . 'packages' . DS . $package;

        if (!is_dir($source)) {
            echo Color::red('Package does not caontains any assets!');
            return;
        }

        if (is_dir($destination)) {
            echo Color::red('Package assets already published!');
            return;
        }

        Storage::cpdir($source, $destination);

        echo Color::green('Assets published for package: ' . $package);
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

        echo Color::green('Assets deleted for package: ' . $package);
    }
}
