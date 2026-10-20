# Using Cache

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Storing Items](#storing-items)
-   [Retrieving Items](#retrieving-items)
-   [Deleting Items](#deleting-items)
-   [Atomic Locks](#atomic-locks)
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

`increment()` adds 1 to a numeric item (starting from 0) and returns the new value; the second argument is the lifetime in minutes (default 1), the third the step. `decrement()` subtracts:

```php
$hits = Cache::increment('page_hits', 60);
$hits = Cache::increment('page_hits', 60, 5);
$left = Cache::decrement('stock:42', 60);
```

Both are atomic on every driver but `memory`, so concurrent requests never lose a count. A memcached counter cannot go below zero.

`add()` stores an item only when the key is not there yet, and returns whether it did. It is atomic on the file, database, redis, memcached and apc drivers: of two requests adding the same key at once, exactly one gets `true`.

```php
if (Cache::add('welcome-mail:'.$user->id, true, 60 * 24)) {
    // send it, once a day at most
}
```

`put_many()` stores several items, and `many()` reads several, with `NULL` for the missing ones:

```php
Cache::put_many(['a' => 1, 'b' => 2], 10);
Cache::many(['a', 'b', 'c']); // ['a' => 1, 'b' => 2, 'c' => null]
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

<a id="atomic-locks"></a>

## Atomic Locks

A lock lets one process at a time run a piece of code, across requests, workers and servers that
share the cache driver. Give it a name and the seconds it lasts, so a process that dies does not
hold it forever:

```php
$lock = Cache::lock('reports:monthly', 120);

if ($lock->get()) {
    // build the report
    $lock->release();
}
```

With a callback, the lock is released afterwards, also when the callback throws, and `get()`
returns the callback's result (`false` when the lock is taken):

```php
Cache::lock('reports:monthly', 120)->get(function () {
    // build the report
});
```

`block()` waits up to some seconds for the lock, and throws a
`System\Exceptions\LockTimeoutException` when it does not get it:

```php
Cache::lock('stock:42', 10)->block(5, function () use ($order) {
    // read and update the stock
});
```

Only the owner releases a lock, so a lock that expired and was taken by another process is left
alone. To release it from elsewhere, for instance from a job, pass the owner token on:

```php
$lock = Cache::lock('import', 600);
$lock->get();

Job::dispatch('import', ['owner' => $lock->owner()]);

// later, in the job
Cache::lock('import', 600, $payload['owner'])->release();
```

> Use a driver the processes share: `database`, `redis` or `memcached` across servers; `file` and
> `apc` on one server. The `memory` driver only locks within one request.

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
