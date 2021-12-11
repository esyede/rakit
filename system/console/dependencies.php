<?php

namespace System\Console;

defined('DS') or exit('No direct script access.');

use System\Container;

// Daftarkan kelas milik command 'make'.
if (! Container::registered('command: make')) {
    Container::register('command: make', function () {
        return new Commands\Make();
    });
}

// Daftarkan kelas milik command 'migrate'.
if (! Container::registered('command: migrate')) {
    Container::register('command: migrate', function () {
        $database = new Commands\Migrate\Database();
        $resolver = new Commands\Migrate\Resolver($database);
        return new Commands\Migrate\Migrator($resolver, $database);
    });
}

// Daftarkan kelas milik command 'package'.
if (! Container::registered('command: package')) {
    Container::register('command: package', function () {
        $repository = Container::resolve('package.repository');
        return new Commands\Package\Packager($repository);
    });
}

// Daftarkan kelas milik command 'key'.
if (! Container::registered('command: key')) {
    Container::singleton('command: key', function () {
        return new Commands\Key();
    });
}

// Daftarkan kelas milik command 'serve'.
if (! Container::registered('command: serve')) {
    Container::singleton('command: serve', function () {
        return new Commands\Serve();
    });
}

// Daftarkan kelas milik command 'clear'.
if (! Container::registered('command: clear')) {
    Container::singleton('command: clear', function () {
        return new Commands\Clear();
    });
}

// Daftarkan kelas milik command 'session'.
if (! Container::registered('command: session')) {
    Container::singleton('command: session', function () {
        return new Commands\Session();
    });
}

// Daftarkan kelas milik command 'route'.
if (! Container::registered('command: route')) {
    Container::singleton('command: route', function () {
        return new Commands\Route();
    });
}

// Daftarkan kelas pengelola repositori paket.
if (! Container::registered('package.repository')) {
    Container::singleton('package.repository', function () {
        return new Commands\Package\Repository();
    });
}

// Daftarkan kelas pengelola repositori paket untuk provider github.
if (! Container::registered('package.provider: github')) {
    Container::singleton('package.provider: github', function () {
        return new Commands\Package\Providers\Github();
    });
}

// Daftarkan kelas pengelola repositori paket untuk provider gitlab.
if (! Container::registered('package.provider: gitlab')) {
    Container::singleton('package.provider: gitlab', function () {
        return new Commands\Package\Providers\Gitlab();
    });
}

// Daftarkan kelas pengelola aset.
if (! Container::registered('package.publisher')) {
    Container::singleton('package.publisher', function () {
        return new Commands\Package\Publisher();
    });
}

// Daftarkan kelas milik command 'help'.
if (! Container::registered('command: help')) {
    Container::singleton('command: help', function () {
        return new Commands\Help();
    });
}

// Daftarkan kelas milik cpmmand 'test'
if (! Container::registered('command: test')) {
    Container::singleton('command: test', function () {
        return new Commands\Test\Runner();
    });
}
