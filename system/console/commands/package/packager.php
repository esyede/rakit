<?php

namespace System\Console\Commands\Package;

defined('DS') or die('No direct script access.');

use System\Container;
use System\File;
use System\Package;
use System\Str;
use System\Console\Commands\Command;

class Packager extends Command
{
    /**
     * Berisi repositori API paket.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Buat instance manajer paket baru.
     *
     * @param Repository $repository
     *
     * @return void
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Download dan instal paket.
     *
     * @param array $names
     *
     * @return void
     */
    public function install($names)
    {
        $this->parameter($names);

        $remote = $this->repository->search($names[0]);

        if (Package::exists($names[0])) {
            echo PHP_EOL.'Package is already registered: '.$names[0].PHP_EOL;
            exit;
        }

        $destination = path('package').$names[0];

        if (is_dir($destination)) {
            echo  PHP_EOL.'Destination directory for this package is already exists in:';
            echo  PHP_EOL.'  '.$destination.PHP_EOL;
            echo  PHP_EOL.'Dou you wish to continue and overwrite it? [y/N] ';

            $stdin = fopen('php://stdin', 'rb');

            if (! in_array(strtolower(trim(fgets($stdin))), ['y', 'yes'])) {
                fclose($stdin);
                throw new \Exception(PHP_EOL.'Operation aborted by user.');
            }

            if (true !== (bool) $remote['maintained']) {
                echo  PHP_EOL.'This package is currently not maintained.';
                echo  PHP_EOL.'Dou you wish to install anyway? [y/N] ';

                if (! in_array(strtolower(trim(fgets($stdin))), ['y', 'yes'])) {
                    fclose($stdin);
                    throw new \Exception(PHP_EOL.'Operation aborted by user.');
                }
            }

            fclose($stdin);
        }

        echo 'Downloading package: '.$names[0];

        $this->download($remote, $destination);
        $this->metadata($remote, $destination);

        echo PHP_EOL.'Package installed successfuly!';

        echo PHP_EOL;
        echo 'Optionally, you can register those package into your application/packages.php'.PHP_EOL;
    }

    /**
     * Uninstal paket.
     *
     * @param array $names
     *
     * @return void
     */
    public function uninstall($names)
    {
        $this->parameter($names);

        if (! Package::exists($names[0])) {
            throw new \Exception(PHP_EOL.sprintf(
                'Error: Package is not registered: %s'.PHP_EOL.
                'Currently registered packages are: '.PHP_EOL.
                '  '.implode(', ', Package::names()).'.',
                $names[0]
            ).PHP_EOL);
        }

        echo 'Uninstalling package: '.$names[0].PHP_EOL;

        // TODO: Perlu dicek apakah suatu paket membuat migration atau tidak
        // sebelum menjalankan migrate:reset agar tidak error.

        // $migrator = Container::resolve('command: migrate');
        // $migrator->reset($names[0]);

        $publisher = Container::resolve('package.publisher');
        $publisher->unpublish($names[0]);

        $location = Package::path($names[0]);
        File::rmdir($location);

        echo 'Package uninstalled successfuly: '.$names[0].PHP_EOL;

        echo PHP_EOL;
        echo 'Now, you have to remove those package entry from your application/packages.php'.PHP_EOL;
    }

    /**
     * Upgrade paket.
     *
     * @param array $names
     *
     * @return void
     */
    public function upgrade($names)
    {
        $this->parameter($names);

        if (! Package::exists($names[0])) {
            throw new \Exception(PHP_EOL.sprintf(
                'Error: Package is not registered: %s'.PHP_EOL.
                'Currently registered packages are: '.PHP_EOL.
                '  '.implode(', ', Package::names()).'.',
                $names[0]
            ).PHP_EOL);
        }

        $remote = $this->repository->search($names[0]);
        $local = path('package').$names[0].DS.'meta.json';
        $latest = $remote['compatibilities'][RAKIT_VERSION];
        $current = 0;

        if (is_file($local)) {
            $current = json_decode(File::get($local), true);
            $current = isset($current['version']) ? $current['version'] : $current;
        }

        if (true !== (bool) $remote['maintained']) {
            echo  PHP_EOL.'This package is currently not maintained.';
            echo  PHP_EOL.'Dou you wish to upgrade anyway? [y/N] ';

            $answer = fgets(STDIN);
            $answer = strtolower(trim($answer));

            if (! in_array($answer, ['y', 'yes'])) {
                throw new \Exception(PHP_EOL.'Operation aborted by user.');
            }
        }

        if ($this->compare($current, $latest, '>=')) {
            echo PHP_EOL.'You already using latest compatible version of this package.'.PHP_EOL;
            exit;
        }

        $destination = Package::path($names[0]);

        File::rmdir($destination);

        $publisher = Container::resolve('package.publisher');
        $publisher->unpublish($names[0]);

        $this->download($remote, $destination);
        $this->metadata($remote, $destination);

        echo PHP_EOL.'Package upgraded successfuly!'.PHP_EOL;
    }

    /**
     * Salin aset milik paket ke direktori root 'assets/'.
     *
     * @param array $names
     *
     * @return void
     */
    public function publish($names)
    {
        $this->parameter($names);

        $publisher = Container::resolve('package.publisher');
        $publisher->publish($names[0]);
    }

    /**
     * Hapus aset milik paket dari direktori root 'assets/'.
     *
     * @param array $names
     *
     * @return void
     */
    public function unpublish($names)
    {
        $this->parameter($names);

        $publisher = Container::resolve('package.publisher');
        $publisher->unpublish($names[0]);
    }

    /**
     * Download paket berdsarkan url provider.
     *
     * @param array  $remote
     * @param string $path
     *
     * @return void
     */
    protected function download(array $remote, $path)
    {
        $provider = $this->hostname($remote);
        Container::resolve('package.provider: '.$provider)->install($remote, $path);
    }

    /**
     * Tambahkan meta.json ke direktori instalasi paket (jika belum ada).
     *
     * @param array  $remote
     * @param string $destination
     */
    protected function metadata(array $remote, $destination)
    {
        $data = [
            'name' => $remote['name'],
            'description' => $remote['description'],
            'version' => $remote['compatibilities'][RAKIT_VERSION],
        ];

        $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if (! is_file($destination = $destination.DS.'meta.json')) {
            File::put($destination, $data);
        }
    }

    /**
     * Bandingkan apakah paket yang digunakan sudah versi paling baru.
     *
     * @param string $current
     * @param string $latest
     * @param string $comparator
     *
     * @return bool
     */
    protected function compare($current, $latest, $comparator = null)
    {
        $current = (int) ltrim(preg_replace('/[^0-9]/', '', $current), '0');
        $latest = (int) ltrim(preg_replace('/[^0-9]/', '', $latest), '0');

        switch ($comparator) {
            case '>':  return $current > $latest;
            case '<':  return $current < $latest;
            case '==': return $current === $latest;
            case '>=': return $current >= $latest;
            case '<=': return $current <= $latest;
            default:   throw new \Exception('Only >, <, ==, >=, and <= comparators are supported.');
        }
    }

    /**
     * Cek apakah nama paket sudah diberikan.
     *
     * @param array $parameters
     *
     * @return void
     */
    protected function parameter($parameters)
    {
        if (0 === count($parameters)) {
            throw new \Exception(PHP_EOL.'Error: Please specify a package name.'.PHP_EOL);
        }
    }

    /**
     * Ambil nama host dari string URL.
     *
     * @param array $remote
     *
     * @return string
     */
    protected function hostname($remote)
    {
        $host = parse_url(trim($remote['repository']))['host'];
        $host = explode('.', $host)[0];
        $provider = '\\System\\Console\\Commands\\Package\\Providers\\'.Str::classify($host);

        if (! class_exists($provider)) {
            throw new \Exception(sprintf('Unsupported package provider: %s', $host));
        }

        return strtolower($host);
    }
}
