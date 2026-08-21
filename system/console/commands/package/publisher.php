<?php

namespace System\Console\Commands\Package;

defined('DS') or die('No direct access.');

use System\Storage;
use System\Package;
use System\Console\Color;

class Publisher
{
    /**
     * Copy assets from package to root 'assets/' directory.
     *
     * @param string $package
     *
     * @return void
     */
    public function publish($package)
    {
        if (!static::named($package)) {
            echo Color::red('Invalid package name: ' . $package);
            return;
        }

        if (!Package::exists($package)) {
            echo Color::red('Package is not registered: ' . $package);
            return;
        }

        $source = path('package') . $package . DS . 'assets';
        $destination = path('assets') . 'packages' . DS . $package;

        if (!is_dir($source)) {
            echo Color::red('Package does not contain any assets!');
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
     * Remove assets from package from root 'assets/' directory.
     *
     * @param string $package
     *
     * @return void
     */
    public function unpublish($package)
    {
        if (!static::named($package)) {
            echo Color::red('Invalid package name: ' . $package);
            return;
        }

        $destination = path('assets') . 'packages' . DS . $package;
        is_dir($destination) && Storage::rmdir($destination);
        echo Color::green('Assets deleted for package: ' . $package);
    }

    /**
     * Check whether the given string is a usable package directory name.
     *
     * @param string $package
     *
     * @return bool
     */
    protected static function named($package)
    {
        return is_string($package) && '' !== $package && preg_match('/^[A-Za-z0-9_.-]+$/', $package)
            && '.' !== $package && '..' !== $package;
    }
}
