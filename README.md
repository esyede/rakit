<h1 align="center">Rakit Web Framework</h1>
<p align="center">
  <img src="https://github.com/esyede/rakit/workflows/build/badge.svg" alt="build"/>
  <img src="https://img.shields.io/github/v/release/esyede/rakit?include_prereleases" alt="release"/>
  <img src="https://img.shields.io/github/languages/top/esyede/rakit" alt="language"/>
  <img src="https://img.shields.io/github/license/esyede/rakit" alt="license"/>
  <img src="https://img.shields.io/github/languages/code-size/esyede/rakit" alt="size"/>
  <img src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg" alt="pulls"/>
  <br>
  <a href="https://rakit.esyede.my.id">https://rakit.esyede.my.id</a>
</p>

## What is Rakit?

Rakit is a lightweight, easy-to-learn PHP web framework built for simplicity and efficiency.
Whether you're a beginner or an experienced developer,
Rakit offers a smooth development experience similar to other popular PHP frameworks,
while giving you the flexibility to write clean, modular code.

## Features

- **Compact Size**: ~1MB Gzipped, including offline documentation.
- **Wide PHP Support**: Compatible with PHP 5.4.0 to PHP 8.x.
- **Flexible Installation**: Works with or without [Composer](https://getcomposer.org).
- **Powerful Routing**: Supports closures and controllers (MVC architecture).
- **Templating Options**: Native views or Blade templating engine.
- **Database Abstraction**: Models and query builders for seamless database interactions.
- **Schema & Migrations**: Easy database schema building and migration tools.
- **Authentication**: Simple, built-in user authentication.
- **CLI Tools**: Command-line utilities for efficient development.
- **And More**: Extensive features to boost your productivity!

## System Requirements

- **PHP**: 5.4.0 to 8.x
- **Extensions**:
    - [Mbstring](https://www.php.net/manual/en/book.mbstring.php)
    - [OpenSSL](https://www.php.net/manual/en/book.openssl.php)
    - [Fileinfo](https://www.php.net/manual/en/book.fileinfo.php)

**Recommended Extensions** (for full functionality):

- [PDO](https://www.php.net/manual/en/pdo.installation.php) for database support (SQLite, MySQL, PostgreSQL, SQL Server).
- [cURL](https://www.php.net/manual/en/book.curl.php) for package installation via console.
- [GD Image](https://www.php.net/manual/en/book.image.php) for image processing.
- [Socket](https://www.php.net/manual/en/book.sockets.php) for WebSocket support.

## Documentation

Every release includes offline documentation, so you can learn even without internet access.
For the latest updates, check out the [official website](https://rakit.esyede.my.id).

New to Rakit? Read the first four groups in order, then pick topics as you need them.

| Group | Topics |
| --- | --- |
| **Prologue** | [Overview](packages/docs/data/home.md) · [Release Notes](packages/docs/data/changelog.md) |
| **Getting Started** | [Installation](packages/docs/data/install.md) · [Directory Structure](packages/docs/data/structure.md) · [Configuration](packages/docs/data/config.md) |
| **Architecture Concepts** | [Container](packages/docs/data/container.md) · [Autoloading](packages/docs/data/autoloading.md) · [Packages](packages/docs/data/packages.md) |
| **The Basics** | [Routing](packages/docs/data/routing.md) · [Controllers](packages/docs/data/controllers.md) · [Requests](packages/docs/data/requests.md) · [Input & Cookies](packages/docs/data/input.md) · [Views & Responses](packages/docs/data/views/home.md) · [Templating](packages/docs/data/views/templating.md) · [URL Generation](packages/docs/data/urls.md) · [Session](packages/docs/data/session/usage.md) · [Validation](packages/docs/data/validation.md) · [Errors & Debugging](packages/docs/data/debugging.md) |
| **Digging Deeper** | [Console](packages/docs/data/console.md) · [Hooks](packages/docs/data/hooks.md) · [Cache](packages/docs/data/cache/usage.md) · [Job](packages/docs/data/job.md) · [Task Scheduling](packages/docs/data/schedule.md) · [Email](packages/docs/data/email.md) · [Notifications](packages/docs/data/notifications.md) · [Broadcasting](packages/docs/data/broadcasting.md) · [Storage](packages/docs/data/storage.md) · [Curl](packages/docs/data/curl.md) · [Processes & Concurrency](packages/docs/data/process.md) · [Localization](packages/docs/data/localization.md) · [WebSocket](packages/docs/data/websocket.md) · [Workers](packages/docs/data/workers.md) · [HTTP Tests](packages/docs/data/testing.md) |
| **Security** | [Authentication](packages/docs/data/auth/usage.md) · [Authorization](packages/docs/data/auth/authorization.md) · [Encryption](packages/docs/data/crypter.md) · [RSA](packages/docs/data/rsa.md) · [Hashing](packages/docs/data/hash.md) · [JWT](packages/docs/data/jwt.md) |
| **Database** | [Configuration](packages/docs/data/database/config.md) · [Raw Queries](packages/docs/data/database/raw.md) · [Query Builder](packages/docs/data/database/magic.md) · [Pagination](packages/docs/data/views/pagination.md) · [Schema Builder](packages/docs/data/database/schema.md) · [Migrations](packages/docs/data/database/migrations.md) · [Faker](packages/docs/data/database/faker.md) · [Redis](packages/docs/data/database/redis.md) |
| **Facile ORM** | [Facile Model](packages/docs/data/database/facile.md) · [Transformers](packages/docs/data/transformers.md) |
| **Utilities** | [Helpers](packages/docs/data/helpers.md) · [Arrays](packages/docs/data/arrays.md) · [Strings](packages/docs/data/strings.md) · [Collection](packages/docs/data/collection.md) · [Carbon](packages/docs/data/carbon.md) · [Messages](packages/docs/data/messages.md) · [Optional](packages/docs/data/optional.md) · [Macroable](packages/docs/data/macroable.md) · [Lottery](packages/docs/data/lottery.md) · [Markdown](packages/docs/data/markdown.md) · [Image](packages/docs/data/image.md) |

## Get Involved

Help shape Rakit! Submit pull requests to the [official repository](https://github.com/esyede/rakit)
or join discussions in our [forum](https://github.com/esyede/rakit/discussions) to assist fellow developers.
You can also donate to support ongoing development and maintenance. Every contribution, big or small, is appreciated!

<a href="https://ko-fi.com/A0A61UOVND" target="_blank"><img height="36" border="0" src="https://storage.ko-fi.com/cdn/kofi6.png?v=6" alt="Buy Me a Coffee at ko-fi.com" /></a>

## License

Rakit is released under the [MIT License](http://www.opensource.org/licenses/mit-license.php). This means,
you can modify, redistribute, and use it for personal or commercial purposes, as long as the original attribution is included.
No warranties or liabilities are provided for any damages.
