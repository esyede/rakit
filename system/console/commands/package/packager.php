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
     * Contains package repository API.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Create a new package manager instance.
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
     * Download and install package.
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
            echo $this->error('Package is already registered: ' . $arguments[0]);
            exit;
        }

        $destination = (string) Package::path($arguments[0]);

        if (is_dir($destination)) {
            echo  PHP_EOL . $this->warning('Destination directory for this package is already exists:');
            echo  PHP_EOL . $this->warning('  ' . $destination);
            echo  PHP_EOL . $this->warning('Dou you wish to continue and overwrite it? [y/N] ', false);

            $stdin = fopen('php://stdin', 'rb');

            if (!in_array(strtolower(trim((string) fgets($stdin))), ['y', 'yes'])) {
                fclose($stdin);
                throw new \Exception(PHP_EOL . 'Operation aborted by user.');
            }

            if (true !== (bool) $remotes['maintained']) {
                echo  PHP_EOL . $this->warning('This package is currently not maintained.');
                echo  $this->warning('Dou you wish to install anyway? [y/N] ', false);

                if (!in_array(strtolower(trim((string) fgets($stdin))), ['y', 'yes'])) {
                    fclose($stdin);
                    throw new \Exception(PHP_EOL . 'Operation aborted by user.');
                }
            }

            fclose($stdin);
        }

        echo $this->info('Downloading package: ') . $arguments[0];

        $this->download($remotes, $destination);
        $this->metadata($remotes, $destination);

        if (is_dir($destination = path('package') . DS . $arguments[0] . DS . 'assets')) {
            echo PHP_EOL . $this->info('Publishing assets...', false);
            Storage::cpdir($destination, path('assets') . 'packages' . DS . $arguments[0]);
            echo $this->info(' done!');
        }

        echo PHP_EOL . $this->info('Package installed successfuly!');

        echo PHP_EOL;
        echo $this->warning('Now, you can register it to your application/packages.php');
    }

    /**
     * Uninstall a package.
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

        echo $this->info('Uninstalling package: ' . $arguments[0]);

        // TODO: We need to check if the package has migrations or not before running migrate:reset to avoid errors.
        // $migrator = Container::resolve('command: migrate');
        // $migrator->reset($arguments[0]);

        if (is_dir($destination = path('package') . DS . $arguments[0])) {
            Storage::rmdir($destination);
        }

        if (is_dir($destination = path('assets') . 'packages' . DS . $arguments[0])) {
            Storage::rmdir($destination);
        }

        echo $this->info('Package uninstalled successfully: ' . $arguments[0]);

        echo PHP_EOL;
        echo $this->warning('Now, you have to remove those package entry from your application/packages.php');
    }

    /**
     * Upgrade a package.
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
            echo PHP_EOL . $this->warning('This package is currently not maintained.');
            echo $this->warning('Dou you wish to upgrade anyway? [y/N] ', false);

            $answer = fgets(STDIN);
            $answer = strtolower((string) trim($answer));

            if (!in_array($answer, ['y', 'yes'])) {
                throw new \Exception(PHP_EOL . 'Operation aborted by user.');
            }
        }

        if ($this->compare($current, $latest, '>=')) {
            echo $this->error('You already using latest compatible version of this package.');
            exit;
        }

        $destination = Package::path($arguments[0]);

        Storage::rmdir($destination);

        $publisher = Container::resolve('package.publisher');
        $publisher->unpublish($arguments[0]);

        $this->download($remotes, $destination);
        $this->metadata($remotes, $destination);

        echo PHP_EOL . $this->info('Package upgraded successfuly!');
    }

    /**
     * Copy assets from package to root 'assets/' directory.
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
     * Delete package's assets from root 'assets/' directory.
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
     * Download package based on provider URL.
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
     * Add meta.json to package installation directory (if not exists).
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
     * Check if the package being used is the latest version.
     *
     * @param string $current
     * @param string $latest
     * @param string $comparator
     *
     * @return bool
     */
    protected function compare($current, $latest, $comparator = null)
    {
        $result = version_compare($current, $latest);

        switch ($comparator) {
            case '>':  return $result === 1;
            case '<':  return $result === -1;
            case '==': return $result === 0;
            case '>=': return $result >= 0;
            case '<=': return $result <= 0;
            default:   throw new \Exception('Only >, <, ==, >=, and <= comparators are supported.');
        }
    }

    /**
     * Check if the package being used is the latest version.
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
     * Get the hostname from a string URL.
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
