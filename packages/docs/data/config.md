# Runtime Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Retrieving Configuration Items](#retrieving-configuration-items)
-   [Setting Configuration Items](#setting-configuration-items)
-   [Environment Configuration](#environment-configuration)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Configuration files live in `application/config/`, each returning an array. The
`Config` class reads and changes their values at runtime using **dot notation**:
the part before the first dot is the file name, the rest is the key path inside
that file. So `application.url` is the `'url'` key of
`application/config/application.php`.

Prefix a key with `package::` to reach a [package's](/docs/packages#using-packages)
config, e.g. `admin::uploads.max_size`.

<a id="retrieving-configuration-items"></a>

## Retrieving Configuration Items

```php
$url = Config::get('application.url');

// With a default for a missing key (otherwise NULL)
$timezone = Config::get('application.timezone', 'UTC');

// A whole file
$database = Config::get('database');

// Does the key exist (and is not NULL)?
if (Config::has('cache.driver')) {
    // ..
}
```

`Config::all()` returns every file loaded so far in this request, grouped by
package (e.g. `$options['application']['database']`). Files that have not been
read yet via `get()` or `set()` are not included.

<a id="setting-configuration-items"></a>

## Setting Configuration Items

```php
Config::set('cache.driver', 'apc');
```

The change lasts for the current request only; nothing is written to the file.

<a id="environment-configuration"></a>

## Environment Configuration

When an environment is active, `application/config/<environment>/<file>.php` is
merged over `application/config/<file>.php`. For example
`application/config/local/database.php` could hold only your local credentials.

> The merge is shallow: a top-level key in the environment file replaces the whole
> key of the main file.

For web requests the environment is detected from the URL patterns in `paths.php`;
console commands read the `--env=` option or the `RAKIT_ENV` environment variable.
