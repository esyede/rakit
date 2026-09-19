# Release Notes

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [v0.9.6 \(pre-release\)](#v096-pre-release)
-   [v0.9.7 \(pre-release\)](#v097-pre-release)
-   [v0.9.8 \(pre-release\)](#v098-pre-release)
-   [v0.9.9 \(pre-release\)](#v099-pre-release)
-   [v0.9.10 \(pre-release\)](#v0910-pre-release)
-   [v0.9.11 \(security\)](#v0911-security)
-   [Unreleased](#unreleased)

<!-- /MarkdownTOC -->

<a id="v096-pre-release"></a>

## v0.9.6 (pre-release)

-   First pre-release version for testing.

<a id="v097-pre-release"></a>

## v0.9.7 (pre-release)

-   Session: fix session guard on file driver.
-   Cache: fix cache guard on file driver.
-   Helpers: remove unused methods.
-   System: use File::xxx for all file operation & Test: move fixtures data into separate folder (test).
-   Docs changes

For more details, please visit [this link](https://github.com/esyede/rakit/releases/tag/v0.9.7).

**Upgrade method**:

-   Replace the `system/` folder with the new one.

<a id="v098-pre-release"></a>

## v0.9.8 (pre-release)

-   Paginator: add new `dots()` method
-   Route: add new methods: `Route::redirect()`, `Route::view()`
-   Date: total rewrite, make it simple
-   Fix: `dump()` helper cannot dump multiple arguments
-   Schema: remove useless foreach loops
-   Config: remove unused method
-   Cookie: set default samesite to 'lax'
-   UI: minor changes on splash page and debugger page
-   Session: set config's session driver data when config file is replaced
-   Console: create session table only if its does not exists
-   Fix: Paginator: `http_build_query()`: Parameter 1 expected to be Array..
-   Response: add default error view for `Response::error()`
-   Fix: `File` session driver not working
-   Cache, Session: change naming scheme to `crc32`
-   Cache, Session: use regular `str_replace` to gain more speed
-   Refactor: rename `File` to `Storage` to avoid ambiguity
-   Refactor: remove `Form` and `HTML` class
-   Blade: add new `@csrf` token
-   Console: add `test` command
-   Refactor: remove `Asset` and `Assetor` class
-   Helpers: `htmlentities` set default encoding to `UTF-8`
-   Console: remove dependency of `ZipArchive` class
-   Refactor: rewrite `Email` component to use driver-based approach

For more details, please visit [this link](https://github.com/esyede/rakit/releases/tag/v0.9.8).

**Upgrade method**:

-   Redownload. Not compatible with previous versions.

<a id="v099-pre-release"></a>

## v0.9.9 (pre-release)

-   Console: allow passing string to `Console::run()`
-   Console: optimize package downloader
-   Model: remove unused `has_one_or_many()` method
-   Email: Fix can't use function return value in write context on PHP 5.4
-   Blade: rename `@yield_section` to @show
-   Blade: remove unused `tap()` method
-   Console: add command example
-   Docs: delete useless models.md
-   Schema: Fix wrong query on `has_column()` - thanks [@reidsneo](https://github.com/reidsneo)
-   Schema: Fix forgot to escape the column
-   Database: `DB::select()` now accept splat parameters
-   Blade: do not run `compile_csrf()` when no `@csrf` called
-   View: use blade for all views
-   Event: rename `Event::listeners()` to `Event::exists()`
-   Str: add comment blocks
-   Console: fix typo of `--database=` cli option
-   Console: rename command `migrate:make` to `make:migration`
-   Console: fix cannot install package
-   Database: make `$operator` parameter as optional in `DB::where()` - thanks [@ZerosDev](https://github.com/ZerosDev)
-   Fix notice undefined index `'autoboot'` - thanks [@ZerosDev](https://github.com/ZerosDev)
-   Docs: fix small typo - thanks [@rhmtty](https://github.com/rhmtty)
-   Docs: add README translation to formal english - thanks [@CxrlosKenobi](https://github.com/CxrlosKenobi)
-   Console: fix forgot to register gitlab provider
-   Session: use '`file'` as default session driver
-   Docs: add composer installation guide

For more details, please visit [this link](https://github.com/esyede/rakit/releases/tag/v0.9.9).

**Upgrade method**:

-   Redownload. Not compatible with previous versions.

<a id="v0910-pre-release"></a>

## v0.9.10 (pre-release)

This release focuses on PHP 5.4 - 8.5 compatibility and resolving class-alias collisions with built-in PHP classes shipped by popular extensions.

### Class rename / alias removal (BREAKING)

Rakit's autoloader uses `class_alias()` to expose framework classes under short global names (`Event`, `Redis`, etc.). Built-in PHP classes are loaded **before** any userland autoloader runs, so when an extension provides a class with the same name, our alias is silently shadowed and you get confusing `Call to undefined method` errors at runtime.

The event dispatcher class itself has been **renamed from `System\Event` to `System\Hook`** so the class name and its short alias stay consistent. The default alias map in `application/config/aliases.php` has been updated to avoid the collisions:

| Old name (class / alias)        | New name / replacement                                                | Conflicting extension                                    |
| ------------------------------- | --------------------------------------------------------------------- | -------------------------------------------------------- |
| `System\Event` / `Event`        | **`System\Hook`** / **`Hook`**                                        | [event](https://pecl.php.net/package/event) (libevent)    |
| `System\Redis` / `Redis`        | `System\Redis` (alias **removed** — use `\System\Redis` or `use System\Redis`) | [redis (phpredis)](https://github.com/phpredis/phpredis) |
| `System\Memcached` / `Memcached`| `System\Memcached` (alias **removed** — use `\System\Memcached` or `use System\Memcached`) | [memcached](https://pecl.php.net/package/memcached)      |

The file `system/event.php` has been removed and its contents now live in `system/hook.php`. The Redis and Memcached classes themselves are unchanged — only their aliases are gone.

#### Migration

Search-and-replace these patterns in your application code:

```php
// Before — short alias
Event::listen('user.login', function ($user) { /* ... */ });
Event::fire('rakit.done', [$response]);

// After
Hook::listen('user.login', function ($user) { /* ... */ });
Hook::fire('rakit.done', [$response]);
```

```php
// Before — fully-qualified name or `use` import
use System\Event;
\System\Event::fire('something');

// After
use System\Hook;
\System\Hook::fire('something');
```

For `Redis` and `Memcached`, either import the class with `use` or call it via FQN:

```php
// Option 1: import once at the top of the file
use System\Redis;

$redis = Redis::db();

// Option 2: fully-qualified name
$redis = \System\Redis::db();
```

> The fully-qualified target classes (`\System\Hook`, `\System\Redis`, `\System\Memcached`) **always work** regardless of alias configuration. If you prefer not to rely on aliases at all, you can use FQN everywhere.

### Autoloader collision detection

`System\Autoloader` gained a new `aliases(array $aliases)` method that registers a batch of aliases and detects conflicts with built-in PHP classes. Conflicting names are skipped (not registered) and a warning is written to STDERR / error log so you know to either disable the conflicting extension or rename the alias.

```php
// New (preferred)
System\Autoloader::aliases(System\Config::get('aliases'));

// Old (still works, but skips conflict detection)
System\Autoloader::$aliases = System\Config::get('aliases');
```

The default `application/boot.php` and `tests/fixtures/application/boot.php` have been updated to use the new method.

### PHP 5.4 - 8.5 compatibility fixes

A pass through the framework has resolved every deprecation, warning, and runtime error reported by PHP 8.4 / 8.5:

-   **Faker** ([system/foundation/faker/provider/base.php](https://github.com/esyede/rakit/blob/main/system/foundation/faker/provider/base.php)): replaced `'static::method'` string callables (deprecated in 8.5) with `[get_called_class(), 'method']`, which works in 5.4+.
-   **Validator** ([system/validator.php](https://github.com/esyede/rakit/blob/main/system/validator.php)): pass the explicit `$escape` parameter to `str_getcsv()` (required from 8.4 onwards to avoid the deprecation notice).
-   **Macroable** ([system/macroable.php](https://github.com/esyede/rakit/blob/main/system/macroable.php)) and **Oops helpers** ([system/foundation/oops/helpers.php](https://github.com/esyede/rakit/blob/main/system/foundation/oops/helpers.php)): guard `Reflection*::setAccessible(true)` behind `PHP_VERSION_ID < 80100` (the call is a no-op on 8.1+ and emits a deprecation on 8.5).
-   **Image** ([system/image.php](https://github.com/esyede/rakit/blob/main/system/image.php)): cast resize/crop dimensions to `int` to silence implicit float→int warnings; switched `imagefilledpolygon()` to the 3-argument form on 8.1+ (the `$num_points` parameter was deprecated); fixed an inverted `PHP_VERSION_ID` check around `imagedestroy()` so the function is no longer called on 8.0+ (where it has no effect and is deprecated in 8.5).
-   **HTTP foundation** ([system/foundation/http/helper.php](https://github.com/esyede/rakit/blob/main/system/foundation/http/helper.php), [system/foundation/http/request.php](https://github.com/esyede/rakit/blob/main/system/foundation/http/request.php)): coerce `null` array keys to `''` (deprecated in 8.1+) and pass `(string)` casts before `preg_split()` calls that previously could receive `null`.
-   **Collection** ([system/collection.php](https://github.com/esyede/rakit/blob/main/system/collection.php)): coerce `null` glue to `''` for `implode()` (deprecated in 8.1+).
-   **Carbon** ([system/carbon.php](https://github.com/esyede/rakit/blob/main/system/carbon.php)): cast `$time` to string before `preg_match()` / `stripos()` to avoid null-to-string deprecations.
-   **PHP 5.4 baseline**: replaced `array_column()` (5.5+) and `boolval()` (5.5+) usages in `system/foundation/oops/defaults.php` and `system/websocket/server.php` with equivalents that work all the way back to 5.4.

### Test suite

-   `php rakit test:core` now passes cleanly on PHP **7.4, 8.2, 8.3, 8.4, and 8.5** (1368 tests, 4764 assertions, no errors or deprecations).
-   `tests/phpunit.php` now silences PHPUnit 4.x's own deprecation chatter (PHPUnit 4 cannot be upgraded without dropping PHP 5.4 support).
-   Test cases that touch private state via reflection now use the two-argument `setValue(null, $value)` form (single-argument call is deprecated since 8.3).

**Upgrade method**:

1.  Redownload the framework. Not compatible with previous versions.
2.  Search your application code for the renamed/removed aliases and apply the migration shown above.
3.  If you maintain custom packages that ship their own `aliases.php`, prefer `Autoloader::aliases([...])` so collisions are reported.

<a id="v0911-security"></a>

## v0.9.11 (security)

Security audit fixes — all severities:

**High**
- `View` `path:` LFI/RFI — `View::resolve_path()` now validates realpath, blocks wrappers, confines to `base/storage/app/system/package`.
- SQLi via `where_date|month|day|year|time` — column is validated and `grammar->wrap()`-ed (`DATE("col")`), `where_column`/`has` operators validated.
- Upload `Input::upload()` — blocks PHP extensions (`php`, `phtml`, `phar`...), validates MIME via `finfo`, size, double extensions.
- Mass-assignment — `Facile\Model::$guarded` accepts `['*']` to guard every attribute; use `$fillable` allowlist.
- `Response::download/file` traversal — `realpath` confinement to allowed roots.
- Open redirect — `Redirect::to()` blocks external hosts (use `away()`), `URL::to()` blocks `//` and `javascript:`.
- `Storage` API — every operation (`get/put/delete/move/copy/cpdir/rmdir/...`) validated via `validate_path()` confinement to `base`.
- CSRF — token no longer accepted from query string (attach the `csrf` middleware to the routes you want to protect).
- Debugger — `detectDebugMode()` no longer auto-allows `127.0.0.1`.
- Blade compiled path — CRC16 → `HMAC-SHA256(RAKIT_KEY, path)` (16 hex chars, 64-bit).
- Auth timing — dummy `Hash::check()` on missing user.

**Medium/Low**
- `@method` now `e()`-escaped, `Header::set` strips CRLF, session/remember cookies force `secure` on HTTPS, remember token rotated, `Crypter` HMAC-SHA256-derived enc/MAC keys (`v=1` payload), `key.php` blocked via `.htaccess`/`sample.htaccess`, operator validation, etc.

**Docs** — `storage.md`, `views/home.md`, `views/templating.md`, `input.md`, `database/facile.md`, `database/magic.md`, `urls.md`, `routing.md`, `debugging.md`, `crypter.md` updated with security notes and safe examples.

<a id="unreleased"></a>

## Unreleased

**Breaking**

-   **Mass assignment is closed by default.** `Facile\Model::$guarded` now defaults
    to `['*']` instead of `[]`, so a model that declares neither `$fillable` nor
    `$guarded` accepts nothing through `fill()` and `create()`. Previously every
    attribute a request named was written, `id` and `is_admin` included.

    `$fillable` now decides on its own: when it is set, `$guarded` is not consulted.
    A model that already declares `$fillable` therefore needs no change. This also
    makes the `$guarded = ['*']` plus `$fillable` combination that v0.9.11 announced
    actually work — until now `$guarded` was checked first and blocked everything,
    so that pairing silently accepted nothing.

    **Upgrade:** give every model an explicit `$fillable`. `make:model` now
    scaffolds one. `fill_raw()` bypasses both lists and is unchanged.

-   `Response::with_cookie()` takes a `$samesite` argument and passes it through to
    `Cookie::put()`, which it previously dropped. Its `$value` now defaults to `''`
    rather than `null`, which always threw.

-   A validation rule that needs a parameter is rejected when written without one.
    `'age' => 'min'` raises `InvalidArgumentException` instead of reading past the
    end of an empty parameter list.

**Security**

-   **HTTP response splitting through a cookie.** The cookie `path` and `domain`
    went into `Set-Cookie` unchecked, so a line break in either carried a header of
    its own into the response. `setcookie()` refuses this, but the worker adapters
    render the header themselves and had no such backstop. Both are now validated
    where every emitted cookie passes through. `Cookie::put()`'s domain check also
    accepted a line break, `FILTER_VALIDATE_DOMAIN` on its own permits one.

-   **Log files, compiled views and debug bar payloads are guarded.** Files written
    under `storage/` that PHP would parse now open with
    `<?php defined('DS') or exit('No direct access.');?>`. A log holds whatever
    reached the application, so an unguarded `.log.php` served by a web server was
    remote code execution. The framework no longer assumes the server was told to
    deny `storage/`. Debug bar payloads moved from `.json` to guarded `.json.php`;
    they carry request data, session contents and every query a request ran.

-   **SSRF through a redirect.** `Curl` follows redirects, and libcurl does that
    itself without going through the URL encoder, so a fetched server could answer
    with `Location: file:///etc/passwd`. Requests and redirects are now limited to
    `http` and `https`.

-   **SQL injection in `->defaults()`.** The column default was wrapped in quotes
    without escaping on MySQL, Postgres and SQL Server; only SQLite escaped it. This
    also broke a default as ordinary as `O'Brien`. Escaping moved to the shared
    grammar, with backslash handling for MySQL.

**Fixed**

-   `insert_get_id()` cast every id to `int`, which turned a UUID or a string key
    into `null`. `Model::save()` then overwrote the key the application had set and
    reported the save as failed although the row was inserted.

-   An emptied `WHERE` list compiled to a bare `WHERE`, and an empty nested clause to
    `()`. Empty `GROUP BY`, `HAVING`, `ORDER BY` and `SELECT` lists produced the same
    kind of dangling keyword.

-   Several optional route segments no longer nest into each other, so
    `users/(:num?)/posts/(:num?)` matches `users/posts` and `users/5/posts` rather
    than requiring the first optional before the literal that follows it.

-   `Model::save()`, `Model::update()` and soft `delete()` wrote a hardcoded
    `'Y-m-d H:i:s'` instead of `static::$date_format`, so they disagreed with
    `touch()` on any model that changed the format.

-   The `date_format`, `after_or_equal` and `before_or_equal` messages left their
    `:format` and `:date` placeholders in the text.

-   `system/init.php` referenced `$stub` outside the branch that defined it, so
    `_ide_helper.php` was never created once `key.php` existed.

**Docs**

-   `database/facile.md`, `input.md`, `views/home.md`, `routing.md`, `validation.md`,
    `debugging.md` and `curl.md` updated for the changes above.
