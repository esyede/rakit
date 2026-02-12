# Runtime Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Retrieving Configuration Items](#retrieving-configuration-items)
-   [Setting Configuration Items](#setting-configuration-items)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Sometimes you may need to change configuration options at runtime. For example, when you

have two database connections and want to switch connections dynamically.

For this need, you can utilize the `Config` component, it uses **dot** syntax

to access files and their configuration options.

<a id="retrieving-configuration-items"></a>

## Retrieving Configuration Items

#### Retrieving a configuration option:

Retrieving the application url configuration:

```php
$value = Config::get('application.url');
```

By default this method will return `NULL` if the data is not found.

However, if you want to change this default return, just pass the default return value that

you want to the second parameter like this:

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
