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
     * @param string $package
     * @param string $path
     *
     * @return void
     */
    abstract public function install($package, $path);

    /**
     * Download dan ekstrak arsip paket yang diberikan.
     *
     * @param string $zipball_url
     * @param array  $package
     * @param string $path
     *
     * @return void
     */
    protected function zipball($zipball_url, $package, $path)
    {
        if (! extension_loaded('zip')) {
            throw new \Exception(PHP_EOL.sprintf(
                'Error: The PHP Zip extension is needed to perform this action.'
            ).PHP_EOL);
        }

        $storage = path('storage').'console'.DS;
        $extractions = $storage.'extractions'.DS;
        $zipball = $storage.'zipball.zip';

        if (! is_dir($extractions)) {
            Storage::mkdir($extractions);
        }

        echo PHP_EOL.'Downloading zipball...';
        $this->download($zipball_url, $zipball);
        echo ' done!';

        echo PHP_EOL.'Extracting zipball...';

        static::unzip($zipball, $extractions);

        $latest = Storage::latest($extractions)->getRealPath();

        @chmod($latest, 0777);

        Storage::mvdir($latest, $path);
        Storage::rmdir($extractions);
        Storage::delete($zipball);

        echo ' done!';
    }

    /**
     * Download arsip zip milik sebuah paket.
     *
     * @param string $zipball_url
     * @param string $destination
     */
    protected function download($zipball_url, $destination)
    {
        Storage::delete($destination);
        $options = [CURLOPT_FOLLOWLOCATION => 1];

        if (200 !== (int) Curl::get($zipball_url, [], $options)->header->http_code) {
            $message = 'Error: Unable to download zipball: %s';
            throw new \Exception(PHP_EOL.sprintf($message, $zipball_url).PHP_EOL);
        }

        try {
            Curl::download($zipball_url, $destination, $options);
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
            Storage::mkdir($destination, 0777);
        }

        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            $open = $zip->open($file);

            if ($open !== true) {
                throw new \Exception('Could not open zip file with ZipArchive.');
            }

            $zip->extractTo($destination);
            $zip->close();
        } else {
            $zip = new PclZip($file);

            if ($zip->extract(77001, $destination) === 0) {
                throw new \Exception('Could not extract zip file with PclZip.');
            }
        }

        return true;
    }
}
