# Konfigurasi Runtime

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#pengetahuan-dasar)
-   [Retrieving Configuration Items](#mengambil-item-konfigurasi)
-   [Setting Configuration Items](#menyetel-item-konfigurasi)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Basic Knowledge

Sometimes you may need to change configuration options at runtime. For example when you
have two database connections and want to switch connections dynamically.

For this need, you can take advantage of the `Config` component, it uses the **dot** syntax
to access the file and its configuration options.

<a id="mengambil-item-konfigurasi"></a>

## Retrieving Configuration Items

#### Retrieving a configuration option:

Retrieving application url configuration:

```php
$value = Config::get('application.url');
```

By default this method will return `NULL` if the configuration was not found.

However, if you want to override this default return value, simply pass the value that
you want to the second parameter as follows:

```php
$value = Config::get('application.timezone', 'UTC');
```

#### Retrieves all options belonging to a configuration file:

```php
$options = Config::get('database');
```

#### Retrieve all configuration data:

```php
$options = Config::all();
```

<a id="menyetel-item-konfigurasi"></a>

## Setting Configuration Items

#### Setting a configuration option:

Sets the cache component so that it uses the APC driver:

```php
Config::set('cache.driver', 'apc');
```
