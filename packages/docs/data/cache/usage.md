# Using Cache

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Storing Items](#storing-items)
-   [Retrieving Items](#retrieving-items)
-   [Deleting Items](#deleting-items)
-   [Cache Driver](#cache-driver)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The `Cache` class keeps frequently read data in fast storage to take load off the database. It uses the driver configured in `application/config/cache.php` (see [Cache Configuration](/docs/cache/config)).

```php
if (Cache::has('users')) {
    $users = Cache::get('users');
}
```

<a id="storing-items"></a>

## Storing Items

`put()` stores a value under a key for the given number of minutes:

```php
Cache::put('name', 'Budi', 10);
```

`forever()` stores an item for five years, which is as good as forever:

```php
Cache::forever('settings', $settings);
```

`increment()` adds 1 to a numeric item (starting from 0) and returns the new value; the second argument is the lifetime in minutes (default 1):

```php
$hits = Cache::increment('page_hits', 60);
```

> Values are serialized for you, so you can store arrays and objects directly.

<a id="retrieving-items"></a>

## Retrieving Items

`get()` returns the item, or `NULL` when it is missing or expired. Pass a default as the second argument; a Closure default only runs on a miss:

```php
$name = Cache::get('name');
$name = Cache::get('name', 'Anonymous');

$count = Cache::get('users_count', function () {
    return DB::table('users')->count();
});
```

`remember($key, $minutes, $callback)` returns the cached value, or runs the Closure, caches its result and returns it. `sear()` does the same but stores the item forever:

```php
$count = Cache::remember('users_count', 60, function () {
    return DB::table('users')->count();
});

$settings = Cache::sear('app_settings', function () {
    return DB::table('settings')->get();
});
```

`pull()` retrieves an item and deletes it in one go:

```php
$token = Cache::pull('token', 'default');
```

**Hit and miss events:**

Every read through `get()` (including `remember()`, `sear()` and `pull()`) fires an event with the key and the lookup time in milliseconds:

```php
Hook::listen('rakit.cache.hit', function ($key, $time) {
    // The item was there
});

Hook::listen('rakit.cache.missed', function ($key, $time) {
    // The item was not
});
```

<a id="deleting-items"></a>

## Deleting Items

`forget()` deletes one item; `flush()` deletes everything:

```php
Cache::forget('name');

Cache::flush();
```

> The `memcached` and `apc` drivers flush the **whole** store, including items that don't belong to your application.

<a id="cache-driver"></a>

## Cache Driver

`Cache::driver()` returns the default driver instance, or a specific one by name (`file`, `database`, `memcached`, `apc`, `redis`, `memory`):

```php
Cache::driver('redis')->put('name', 'Budi', 10);
Cache::driver('file')->forever('config', $config);
```

Register a custom driver with `extend()`. The resolver must return a `System\Cache\Drivers\Driver` instance:

```php
Cache::extend('mongo', function () {
    return new MongoCacheDriver();
});

Cache::driver('mongo')->put('name', 'Budi', 10);
```
