<?php

namespace System\Console\Commands\Package\Providers;

defined('DS') or exit('No direct script access.');

use System\Curl;
use System\Storage;

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
            throw new \Exception(sprintf('Package already downloaded: %s', $package['name']));
        }

        chmod(Storage::latest(path('package'))->getRealPath(), 0755);
        echo PHP_EOL.'Downloading zipball...';
        $this->download($url, $zipball);
        echo ' done!';

        echo PHP_EOL.'Extracting zipball...';

        static::unzip($zipball, path('package'));

        $packages = glob(path('package').$package['name'].'*', GLOB_ONLYDIR);

        if (isset($packages[0]) && basename($packages[0]) !== $package['name']) {
            rename($packages[0], path('package').$package['name']);
        }

        chmod(Storage::latest(path('package'))->getRealPath(), 0755);
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

        $year = (int) gmdate('Y');
        $version = rand(76, 80) + ((($year < 2020) ? 2020 : $year) - 2020);
        $agent = 'Mozilla/5.0 (Linux x86_64; rv:'.$version.'.0) Gecko/20100101 Firefox/'.$version.'.0';
        $options = [
            CURLOPT_HTTPGET => 1,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_USERAGENT => $agent,
            CURLOPT_VERBOSE => get_cli_option('verbose') ? 1 : 0,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 1,
            CURLOPT_NOBODY => 1,
        ]);
        $unused = curl_exec($ch);
        $header = (object) curl_getinfo($ch);

        if (false === strpos($header->content_type, 'application/zip')) {
            echo PHP_EOL.sprintf(
                "Error: Remote sever sending an invalid content type: '%s', expecting '%s'",
                $header->content_type ? $header->content_type : 'null', 'application/zip'
            ).PHP_EOL;
            exit;
        }

        try {
            $fopen = fopen($destination, 'w+');
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_FILE => $fopen,
                CURLOPT_BINARYTRANSFER => 1,
            ]);

            if (false === curl_exec($ch)) {
                echo PHP_EOL.'Error: '.curl_error($ch).PHP_EOL;
                exit;
            }

            curl_close($ch);
            fclose($fopen);
        } catch (\Throwable $e) {
            echo PHP_EOL.'Error: '.$e->getMessage().PHP_EOL;
            exit;
        } catch (\Exception $e) {
            echo PHP_EOL.'Error: '.$e->getMessage().PHP_EOL;
            exit;
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

        if (! extension_loaded('zip') || ! class_exists('\ZipArchive')) {
            echo PHP_EOL.'Please enable php-zip extension on this server'.PHP_EOL;
            exit;
        }

        $zip = new \ZipArchive();

        if (! $zip->open($file)) {
            echo PHP_EOL.sprintf('Error: Could not open zip file: ', $file).PHP_EOL;
            exit;
        }

        $zip->extractTo($destination);
        $zip->close();
        return true;
    }
}
