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
        $storage = path('storage').'console'.DS;
        $extractions = $storage.'extractions'.DS;
        $zipball = $storage.'zipball.zip';

        if (! is_dir($extractions)) {
            Storage::mkdir($extractions);
        }

        echo PHP_EOL.'Downloading zipball...';
        $this->download($url, $zipball);
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
     * @param string $url
     * @param string $destination
     */
    protected function download($url, $destination)
    {
        Storage::delete($destination);
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
            Storage::mkdir($destination, 0777);
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
