<?php

namespace System\Console;

defined('DS') or exit('No direct access.');

use System\Container;

// Register classes for the 'make' command.
if (!Container::registered('command: make')) {
    Container::register('command: make', function () {
        return new Commands\Make();
    });
}

// Register classes for the 'migrate' command.
if (!Container::registered('command: migrate')) {
    Container::register('command: migrate', function () {
        $database = new Commands\Migrate\Database();
        $resolver = new Commands\Migrate\Resolver($database);
        return new Commands\Migrate\Migrator($resolver, $database);
    });
}

// Register classes for the 'job' command.
if (!Container::registered('command: job')) {
    Container::register('command: job', function () {
        return new Commands\Job();
    });
}

// Register classes for the 'package' command.
if (!Container::registered('command: package')) {
    Container::register('command: package', function () {
        $repository = Container::resolve('package.repository');
        return new Commands\Package\Packager($repository);
    });
}

// Register classes for the 'serve' command.
if (!Container::registered('command: serve')) {
    Container::singleton('command: serve', function () {
        return new Commands\Serve();
    });
}

// Register classes for the 'clear' command.
if (!Container::registered('command: clear')) {
    Container::singleton('command: clear', function () {
        return new Commands\Clear();
    });
}

// Register classes for the 'session' command.
if (!Container::registered('command: session')) {
    Container::singleton('command: session', function () {
        return new Commands\Session();
    });
}

// Register classes for the 'route' command.
if (!Container::registered('command: route')) {
    Container::singleton('command: route', function () {
        return new Commands\Route();
    });
}

// Register the package repository manager class.
if (!Container::registered('package.repository')) {
    Container::singleton('package.repository', function () {
        return new Commands\Package\Repository();
    });
}

// Register the package repository manager class for the github provider.
if (!Container::registered('package.provider: github')) {
    Container::singleton('package.provider: github', function () {
        return new Commands\Package\Providers\Github();
    });
}

// Register the package repository manager class for the gitlab provider.
if (!Container::registered('package.provider: gitlab')) {
    Container::singleton('package.provider: gitlab', function () {
        return new Commands\Package\Providers\Gitlab();
    });
}

// Register classes for the package publisher.
if (!Container::registered('package.publisher')) {
    Container::singleton('package.publisher', function () {
        return new Commands\Package\Publisher();
    });
}

// Register classes for the 'help' command.
if (!Container::registered('command: help')) {
    Container::singleton('command: help', function () {
        return new Commands\Help();
    });
}

// Register classes for the 'test' command.
if (!Container::registered('command: test')) {
    Container::singleton('command: test', function () {
        return new Commands\Test\Runner();
    });
}

// Register classes for the 'websocket' command.
if (!Container::registered('command: websocket')) {
    Container::singleton('command: websocket', function () {
        return new Commands\Websocket();
    });
}
