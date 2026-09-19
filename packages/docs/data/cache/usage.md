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

The cache keeps frequently read data in faster storage, taking load off the database. Drivers: File, Database, Memcached, Redis, APC and Memory, configured in `application/config/cache.php`.

**Checking if an item exists in cache:**

```php
if (Cache::has('users')) {
    $users = Cache::get('users');
}
```

<a id="storing-items"></a>

## Storing Items

`put()` stores an item:

```php
Cache::put('name', 'Budi', 10);
```

The parameters are the key the item is read back by, its value, and how many minutes to keep it.

**Storing an item forever:**

`forever()` stores an item for five years, which is as good as forever:

```php
Cache::forever('settings', $settings);
Cache::forever('config', Config::get('application'));
```

**Storing multiple items:**

```php
Cache::put('user:1', $user, 60);
Cache::put('user:2', $user2, 60);
Cache::put('posts', $posts, 30);
```

**Example storing query results:**

```php
// Cache query results for 60 minutes
$users = DB::table('users')->get();
Cache::put('all_users', $users, 60);

// Cache with dynamic key
$user_id = 1;
$user = User::find($user_id);
Cache::put("user:{$user_id}", $user, 30);
```

> There is no need to serialize objects when storing them in cache because Rakit will do it for you.

<a id="retrieving-items"></a>

## Retrieving Items

`get()` reads an item back by its key:

```php
$name = Cache::get('name');
```

A missing or expired item gives `NULL`, unless a default is passed as the second argument:

```php
$name = Cache::get('name', 'Anonymous');
```

**Using Closure as default value:**

A Closure as the default only runs when the item is missing, so an expensive lookup costs nothing on a hit:

```php
$users = Cache::get('users_count', function () {
    return DB::table('users')->count();
});
```

**The remember() method:**

`remember()` returns the cached value if it exists, otherwise it runs the
Closure, caches its return value, and returns it. The argument order is
`($key, $minutes, $callback)`:

```php
$count = Cache::remember('users_count', 60, function () {
    return DB::table('users')->count();
});
```

**The sear() method:**

The `sear()` method is like `remember()`, but stores the item forever:

```php
$settings = Cache::sear('app_settings', function () {
    return DB::table('settings')->get();
});
```

**Check if an item exists:**

`has()` answers whether an item is there:

```php
if (Cache::has('users')) {
    $users = Cache::get('users');
} else {
    $users = DB::table('users')->get();
    Cache::put('users', $users, 60);
}
```

**Practical example:**

```php
// Cache data with dynamic key
public function get_user($id)
{
    return Cache::remember("user:{$id}", 30, function () use ($id) {
        return User::find($id);
    });
}

// Cache complex query results
public function get_popular_posts()
{
    return Cache::remember('popular_posts', 120, function () {
        return Post::where('published', '=', true)
            ->order_by('views', 'desc')
            ->take(10)
            ->get();
    });
}
```

<a id="deleting-items"></a>

## Deleting Items

To delete an item from the cache, use the `forget()` method:

```php
Cache::forget('name');
```

**Deleting several items:**

```php
Cache::forget('users');
Cache::forget('posts');
Cache::forget('comments');
```

**Flush the entire cache:**

You can delete all items from the cache using the `flush()` method:

```php
Cache::flush();
```

> Be careful with `flush()` as it will delete **all** items in the cache. With some drivers (such as `memcached`, `apc`,
> or `redis` with an empty cache key) that includes items not belonging to your application.

**Example deleting cache after updating data:**

```php
public function action_update($id)
{
    // Update user
    $user = User::find($id);
    $user->name = Input::get('name');
    $user->save();

    // Delete old user cache
    Cache::forget("user:{$id}");
    Cache::forget('all_users');

    return Redirect::to('users');
}
```

<a id="cache-driver"></a>

## Cache Driver

The driver comes from `application/config/cache.php`, but another one can be picked at runtime:

**Using a specific driver:**

```php
// Use Memcached driver
Cache::driver('memcached')->put('name', 'Budi', 10);

// Use Redis driver
Cache::driver('redis')->put('name', 'Budi', 10);

// Use File driver
Cache::driver('file')->put('name', 'Budi', 10);
```

**Example using multiple drivers:**

```php
// Store in file cache for rarely changing data
Cache::driver('file')->forever('config', $config);

// Store in Redis for frequently accessed data
Cache::driver('redis')->put('session:' . $user_id, $session, 120);

// Store in Memcached for temporary data
Cache::driver('memcached')->put('temp_data', $data, 5);
```

**Available drivers:**

- `file` - Stores cache in filesystem
- `database` - Stores cache in database
- `memcached` - Memcached cache driver
- `redis` - Redis cache driver
- `memory` - Stores cache in memory for the current request only
- `apc` - APC cache driver (PHP extension)

```php
// Check the currently used driver
$driver = Cache::driver(); // Returns default driver instance
```
