# Runtime Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Retrieving Configuration Items](#retrieving-configuration-items)
-   [Setting Configuration Items](#setting-configuration-items)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Config` reads and writes configuration values at runtime. It uses **dot
notation** to address nested keys: the part before the first dot is the file
name (relative to `application/config/`), the rest is the key path inside
that file.

For example, `Config::get('application.url')` reads the `'url'` key from
`application/config/application.php`.

<a id="retrieving-configuration-items"></a>

## Retrieving Configuration Items

#### Retrieving a configuration option:

```php
$value = Config::get('application.url');
```

`get()` returns `NULL` when the key is missing. Pass a default as the second
argument if you want a different fallback:

```php
$value = Config::get('application.timezone', 'UTC');
```

#### Retrieving all options of a configuration file:

```php
$options = Config::get('database');
```

#### Retrieving all configuration data:

```php
$options = Config::all();
```

<a id="setting-configuration-items"></a>

## Setting Configuration Items

#### Setting a configuration option:

Setting the cache component to use the APC driver.

```php
Config::set('cache.driver', 'apc');
```
