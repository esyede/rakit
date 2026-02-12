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

Cache provides a mechanism to store frequently accessed data in faster storage, thereby reducing database load and improving application performance.

Rakit supports various cache drivers such as File, Database, Memcached, Redis, and APC. Cache configuration can be set in `application/config/cache.php`.

**Checking if an item exists in cache:**

```php
if (Cache::has('users')) {
    $users = Cache::get('users');
}
```

<a id="storing-items"></a>

## Storing Items

Storing items in the cache is very simple. Just call the `put()` method like this:

```php
Cache::put('name', 'Budi', 10);
```

The first parameter is the key of the cache item. You will use this key to retrieve the item from the cache. The second parameter is its value. The third parameter is the number of `minutes` you want the item to be cached.

**Storing an item forever:**

The `forever()` method stores an item without an expiration time limit:

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
$userId = 1;
$user = User::find($userId);
Cache::put("user:{$userId}", $user, 30);
```

> There is no need to serialize objects when storing them in cache because Rakit will do it for you.

<a id="retrieving-items"></a>

## Retrieving Items

Retrieving items from the cache is even easier than storing them. Just use the `get()` method and specify which item's key you want to retrieve:

```php
$name = Cache::get('name');
```

By default, it will return `NULL` if the requested item is not found or has expired. However, you can also provide a different default value as the second parameter if you wish:

```php
$name = Cache::get('name', 'Anonymous');
```

Now, it will return `'Anonymous'` if the `'name'` cache is not found or has expired.

**Using Closure as default value:**

What if you need a value from the database while the cache item is not found? The solution is simple. You can pass a Closure to the `get()` method as the default value. The Closure will only be executed if the cached item does not exist:

```php
$users = Cache::get('users_count', function () {
    return DB::table('users')->count();
});
```

**The remember() method:**

The `remember()` method retrieves an item from the cache, and if it doesn't exist, it will execute the Closure and store the result in the cache:

```php
$users = Cache::remember('users_count', function () {
    return DB::table('users')->count();
}, 60);
```

Let's discuss the example above. If the `'users_count'` item exists in the cache, that will be returned. If it doesn't exist, the return value from the Closure will be stored in the cache for 60 minutes and returned at the same time.

**The sear() method:**

The `sear()` method is like `remember()`, but stores the item forever:

```php
$settings = Cache::sear('app_settings', function () {
    return DB::table('settings')->get();
});
```

**Check if an item exists:**

Rakit gives you a simple way to determine if an item exists in the cache using the `has()` method:

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
    return Cache::remember("user:{$id}", function () use ($id) {
        return User::find($id);
    }, 30);
}

// Cache complex query results
public function get_popular_posts()
{
    return Cache::remember('popular_posts', function () {
        return Post::where('published', '=', true)
            ->order_by('views', 'desc')
            ->take(10)
            ->get();
    }, 120);
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

> Be careful with `flush()` as it will delete **all** items in the cache, not just those belonging to your application.

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

By default, Rakit uses the driver configured in `application/config/cache.php`. However, you can use a different driver at runtime:

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
Cache::driver('redis')->put('session:' . $userId, $session, 120);

// Store in Memcached for temporary data
Cache::driver('memcached')->put('temp_data', $data, 5);
```

**Available drivers:**

- `file` - Stores cache in filesystem
- `database` - Stores cache in database
- `memcached` - Memcached cache driver
- `redis` - Redis cache driver
- `apc` - APC cache driver (PHP extension)

```php
// Check the currently used driver
$driver = Cache::driver(); // Returns default driver instance
```
