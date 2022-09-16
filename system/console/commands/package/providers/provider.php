<?php

namespace System\Console\Commands\Package\Providers;

defined('DS') or exit('No direct script access.');

use System\Curl;
use System\Storage;
use System\Console\PclZip;

abstract class Provider
{
    /**
     * Download paket yang diberikan.
     *
     * @param array  $package
     * @param string $path
     *
     * @return void
     */
    abstract public function install(array $package, $path);

    /**
     * Download dan ekstrak arsip paket yang diberikan.
     *
     * @param string $url
     * @param array  $package
     * @param string $path
     *
     * @return void
     */
    protected function zipball($url, array $package, $path)
    {
        $zipball = path('storage').'console'.DS.'zipball.zip';

        if (is_file($zipball)) {
            Storage::delete($zipball);
        }

        if (is_dir(path('package').$package['name'])) {
            echo PHP_EOL;
            throw new \Exception(sprintf('Package already instantiated: %s', $package['name']));
        }

        @chmod(Storage::latest(path('package'))->getRealPath(), 0755);

        echo PHP_EOL.'Downloading zipball...';
        $this->download($url, $zipball);
        echo ' done!';

        echo PHP_EOL.'Extracting zipball...';

        static::unzip($zipball, path('package'));

        $packages = glob(path('package').$package['name'].'*', GLOB_ONLYDIR);

        if (isset($packages[0]) && basename($packages[0]) !== $package['name']) {
            rename($packages[0], path('package').$package['name']);
        }

        @chmod(Storage::latest(path('package'))->getRealPath(), 0755);
        Storage::delete($zipball);

        if (is_dir($assets = path('package').$package['name'].DS.'assets')) {
            $destination = path('assets').'packages'.DS.$package['name'];

            if (! is_dir($destination)) {
                Storage::cpdir($assets, $destination);
            } else {
                echo PHP_EOL;
                throw new \Exception(sprintf('Assets already exists: %s', $destination));
            }
        }

        echo ' done!';
    }

    /**
     * Download arsip zip milik sebuah paket.
     *
     * @param string $url
     * @param string $destination
     */
    protected function download($url, $destination)
    {
        if (is_dir($destination)) {
            Storage::delete($destination);
        }

        $options = [CURLOPT_FOLLOWLOCATION => 1, CURLOPT_HEADER => 1, CURLOPT_NOBODY => 1];
        $remote = Curl::get($url, [], $options);
        $content_type = isset($remote->header->content_type)
            ? $remote->header->content_type
            : null;

        if ('application/zip' !== $content_type) {
            throw new \Exception(PHP_EOL.sprintf(
                "Error: Remote sever sending an invalid content type header: '%s', expecting '%s'",
                $content_type, 'application/zip'
            ).PHP_EOL);
        }

        unset($options[CURLOPT_HEADER], $options[CURLOPT_NOBODY]);

        try {
            Curl::download($url, $destination, $options);
        } catch (\Throwable $e) {
            throw new \Exception(PHP_EOL.'Error: '.$e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception(PHP_EOL.'Error: '.$e->getMessage());
        }
    }

    /**
     * Unzip arsip paket.
     *
     * @param string $file
     * @param string $destination
     *
     * @return bool
     */
    public static function unzip($file, $destination)
    {
        @ini_set('memory_limit', '256M');

        if (! is_dir($destination)) {
            Storage::mkdir($destination, 0755);
        }

        if (extension_loaded('zip') && class_exists('\ZipArchive')) {
            $zip = new \ZipArchive();
            $open = $zip->open($file);

            if (true !== $open) {
                throw new \Exception(PHP_EOL.'Error: Could not open zip file with ZipArchive.');
            }

            $zip->extractTo($destination);
            $zip->close();
        } else {
            $zip = new PclZip($file);

            if (0 === $zip->extract(77001, $destination)) {
                throw new \Exception(PHP_EOL.'Error: Could not extract zip file with PclZip.');
            }
        }

        return true;
    }
}
