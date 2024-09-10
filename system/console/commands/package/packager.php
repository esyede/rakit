<?php

namespace System\Console\Commands\Package;

defined('DS') or die('No direct access.');

use System\Container;
use System\Storage;
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
     * @param array $arguments
     *
     * @return void
     */
    public function install(array $arguments)
    {
        $this->parameter($arguments);

        $remotes = $this->repository->search($arguments[0]);

        if (Package::exists($arguments[0])) {
            echo PHP_EOL . 'Package is already registered: ' . $arguments[0] . PHP_EOL;
            exit;
        }

        $destination = (string) Package::path($arguments[0]);

        if (is_dir($destination)) {
            echo  PHP_EOL . 'Destination directory for this package is already exists in:';
            echo  PHP_EOL . '  ' . $destination . PHP_EOL;
            echo  PHP_EOL . 'Dou you wish to continue and overwrite it? [y/N] ';

            $stdin = fopen('php://stdin', 'rb');

            if (!in_array(strtolower(trim((string) fgets($stdin))), ['y', 'yes'])) {
                fclose($stdin);
                throw new \Exception(PHP_EOL . 'Operation aborted by user.');
            }

            if (true !== (bool) $remotes['maintained']) {
                echo  PHP_EOL . 'This package is currently not maintained.';
                echo  PHP_EOL . 'Dou you wish to install anyway? [y/N] ';

                if (!in_array(strtolower(trim((string) fgets($stdin))), ['y', 'yes'])) {
                    fclose($stdin);
                    throw new \Exception(PHP_EOL . 'Operation aborted by user.');
                }
            }

            fclose($stdin);
        }

        echo 'Downloading package: ' . $arguments[0];

        $this->download($remotes, $destination);
        $this->metadata($remotes, $destination);

        if (is_dir($destination = path('package') . DS . $arguments[0] . DS . 'assets')) {
            echo PHP_EOL . 'Publishing assets...';
            Storage::cpdir($destination, path('assets') . 'packages' . DS . $arguments[0]);
            echo ' done!' . PHP_EOL;
        }

        echo PHP_EOL . 'Package installed successfuly!';

        echo PHP_EOL;
        echo 'Now, you can register it to your application/packages.php' . PHP_EOL;
    }

    /**
     * Uninstal paket.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function uninstall(array $arguments)
    {
        $this->parameter($arguments);

        if (!Package::exists($arguments[0])) {
            throw new \Exception(PHP_EOL . sprintf(
                'Error: Package is not registered: %s' . PHP_EOL .
                    'Currently registered packages are: ' . PHP_EOL .
                    '  ' . implode(', ', Package::names()) . '.',
                $arguments[0]
            ) . PHP_EOL);
        }

        echo 'Uninstalling package: ' . $arguments[0] . PHP_EOL;

        // TODO: Perlu dicek apakah suatu paket membuat migration atau tidak
        // sebelum menjalankan migrate:reset agar tidak error.

        // $migrator = Container::resolve('command: migrate');
        // $migrator->reset($arguments[0]);

        if (is_dir($destination = path('package') . DS . $arguments[0])) {
            Storage::rmdir($destination);
        }

        if (is_dir($destination = path('assets') . 'packages' . DS . $arguments[0])) {
            Storage::rmdir($destination);
        }

        echo 'Package uninstalled successfuly: ' . $arguments[0] . PHP_EOL;

        echo PHP_EOL;
        echo 'Now, you have to remove those package entry from your application/packages.php' . PHP_EOL;
    }

    /**
     * Upgrade paket.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function upgrade(array $arguments)
    {
        $this->parameter($arguments);

        if (!Package::exists($arguments[0])) {
            throw new \Exception(PHP_EOL . sprintf(
                'Error: Package is not registered: %s' . PHP_EOL .
                    'Currently registered packages are: ' . PHP_EOL .
                    '  ' . implode(', ', Package::names()) . '.',
                $arguments[0]
            ) . PHP_EOL);
        }

        $remotes = $this->repository->search($arguments[0]);
        $local = path('package') . $arguments[0] . DS . 'meta.json';
        $latest = $remotes['compatibilities']['v' . RAKIT_VERSION];
        $current = 0;

        if (is_file($local)) {
            $current = json_decode(Storage::get($local), true);
            $current = isset($current['version']) ? $current['version'] : $current;
        }

        if (true !== (bool) $remotes['maintained']) {
            echo  PHP_EOL . 'This package is currently not maintained.';
            echo  PHP_EOL . 'Dou you wish to upgrade anyway? [y/N] ';

            $answer = fgets(STDIN);
            $answer = strtolower((string) trim($answer));

            if (!in_array($answer, ['y', 'yes'])) {
                throw new \Exception(PHP_EOL . 'Operation aborted by user.');
            }
        }

        if ($this->compare($current, $latest, '>=')) {
            echo PHP_EOL . 'You already using latest compatible version of this package.' . PHP_EOL;
            exit;
        }

        $destination = Package::path($arguments[0]);

        Storage::rmdir($destination);

        $publisher = Container::resolve('package.publisher');
        $publisher->unpublish($arguments[0]);

        $this->download($remotes, $destination);
        $this->metadata($remotes, $destination);

        echo PHP_EOL . 'Package upgraded successfuly!' . PHP_EOL;
    }

    /**
     * Salin aset milik paket ke direktori root 'assets/'.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function publish(array $arguments)
    {
        $this->parameter($arguments);

        $publisher = Container::resolve('package.publisher');
        $publisher->publish($arguments[0]);
    }

    /**
     * Hapus aset milik paket dari direktori root 'assets/'.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function unpublish(array $arguments)
    {
        $this->parameter($arguments);

        $publisher = Container::resolve('package.publisher');
        $publisher->unpublish($arguments[0]);
    }

    /**
     * Download paket berdsarkan url provider.
     *
     * @param array  $remotes
     * @param string $path
     *
     * @return void
     */
    protected function download(array $remotes, $path)
    {
        $provider = $this->hostname($remotes);
        Container::resolve('package.provider: ' . $provider)->install($remotes, $path);
    }

    /**
     * Tambahkan meta.json ke direktori instalasi paket (jika belum ada).
     *
     * @param array  $remotes
     * @param string $destination
     */
    protected function metadata(array $remotes, $destination)
    {
        $data = [
            'name' => $remotes['name'],
            'description' => $remotes['description'],
            'version' => $remotes['compatibilities']['v' . RAKIT_VERSION],
        ];

        $data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if (!is_file($destination = $destination . DS . 'meta.json')) {
            Storage::put($destination, $data);
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
            case '>':
                return $current > $latest;

            case '<':
                return $current < $latest;

            case '==':
                return $current === $latest;

            case '>=':
                return $current >= $latest;

            case '<=':
                return $current <= $latest;

            default:
                throw new \Exception('Only >, <, ==, >=, and <= comparators are supported.');
        }
    }

    /**
     * Cek apakah nama paket sudah diberikan.
     *
     * @param array $parameters
     *
     * @return void
     */
    protected function parameter(array $parameters)
    {
        if (0 === count($parameters)) {
            throw new \Exception(PHP_EOL . 'Error: Please specify a package name.' . PHP_EOL);
        }
    }

    /**
     * Ambil nama host dari string URL.
     *
     * @param array $remotes
     *
     * @return string
     */
    protected function hostname($remotes)
    {
        $host = parse_url(trim($remotes['repository']));
        $host = explode('.', isset($host['host']) ? $host['host'] : 'Unknown');
        $host = isset($host[0]) ? $host[0] : 'Unknown';
        $provider = '\\System\\Console\\Commands\\Package\\Providers\\' . Str::classify($host);

        if (!class_exists($provider)) {
            throw new \Exception(sprintf('Unsupported package provider: %s', $host));
        }

        return strtolower($host);
    }
}
