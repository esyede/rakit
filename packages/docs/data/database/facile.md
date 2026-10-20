# Facile Model

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Defining Models](#defining-models)
  - [Conventions](#conventions)
  - [Model Properties](#model-properties)
- [Retrieving Models](#retrieving-models)
  - [Retrieving All Records](#retrieving-all-records)
  - [Retrieving by Primary Key](#retrieving-by-primary-key)
  - [Not Found Exceptions](#not-found-exceptions)
  - [Building Queries](#building-queries)
  - [Chunking & Lazy Iteration](#chunking--lazy-iteration)
- [Inserting & Updating Models](#inserting--updating-models)
  - [Inserting](#inserting)
  - [Updating](#updating)
  - [Retrieving or Creating](#retrieving-or-creating)
- [Deleting Models](#deleting-models)
  - [Soft Deleting](#soft-deleting)
- [Query Scopes](#query-scopes)
  - [Global Scopes](#global-scopes)
  - [Local Scopes](#local-scopes)
- [Timestamps](#timestamps)
- [Mass Assignment](#mass-assignment)
  - [Fillable](#fillable)
  - [Guarded](#guarded)
- [Relationships](#relationships)
  - [One To One](#one-to-one)
  - [One To Many](#one-to-many)
  - [Many To Many](#many-to-many)
  - [Has Many Through](#has-many-through)
  - [Polymorphic Relations](#polymorphic-relations)
- [Querying Relationships](#querying-relationships)
- [Inserting Related Models](#inserting-related-models)
  - [Pivot Tables](#pivot-tables)
- [Eager Loading](#eager-loading)
  - [Constraining Eager Loads](#constraining-eager-loads)
- [Accessors & Mutators](#accessors--mutators)
- [Attribute Casting](#attribute-casting)
- [Serialization](#serialization)
- [Validation](#validation)
- [Model Events](#model-events)
  - [Cancelling an Operation](#cancelling-an-operation)
  - [Booting](#booting)
- [Observers](#observers)
- [Model Helpers](#model-helpers)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Facile is Rakit's ORM. Each database table maps to a model class and each row to an instance of it, so you query, insert, update and delete records by calling methods instead of writing SQL.

<a id="defining-models"></a>

## Defining Models

A model is a class extending `Facile`:

```php
class User extends Facile
{
    // Conventions take care of everything by default.
}
```

<a id="conventions"></a>

### Conventions

- **Table**: the lowercase plural of the class name. `User` → `users`, `Order_Item` → `order_items` (but `OrderItem` → `orderitems`).
- **Primary key**: an auto-increment `id` column.
- **Timestamps**: `created_at` and `updated_at` columns, filled automatically.
- **Soft deletes**: a `deleted_at` column, when enabled.

<a id="model-configuration"></a>
<a id="available-properties"></a>
<a id="model-properties"></a>

### Model Properties

Override the conventions and configure behaviour with these properties (shown with example values; defaults in comments):

```php
class User extends Facile
{
    public static $table = 'my_users';        // default: plural of class name
    public static $key = 'user_id';           // default: 'id'
    public static $connection = 'mysql';      // default: default connection
    public static $sequence = 'users_id_seq'; // PostgreSQL sequence, default: null
    public static $timestamps = true;         // default: true
    public static $perpage = 15;              // paginate() page size, default: 20
    public static $soft_delete = false;       // default: false

    public static $fillable = ['name', 'email'];   // default: null
    public static $guarded = ['id'];               // default: ['*']

    public static $hidden = ['password'];     // default: []
    public static $visible = [];              // default: []
    public static $appends = ['full_name'];   // default: []
    public static $casts = ['active' => 'boolean']; // default: []
    public static $date_format = 'Y-m-d H:i:s';     // default

    public $with = ['posts', 'profile'];      // always eager load, default: []

    public static $rules = ['name' => 'required|min:3']; // default: []
    public static $messages = ['required' => ':attribute is required']; // default: []
}
```

Each property is explained in its own section below.

<a id="retrieving-models"></a>

## Retrieving Models

<a id="retrieving-all-records"></a>

### Retrieving All Records

```php
foreach (User::all() as $user) {
    echo $user->name;
}
```

`all()`, `get()` and `paginate()` results are a `Collection` of models, so the full collection API is available. It is still countable, iterable and array-accessible (`count($users)`, `$users[0]`):

```php
$names = User::all()->pluck('name');
$grouped = User::all()->group_by('role');
```

<a id="retrieving-by-primary-key"></a>

### Retrieving by Primary Key

`find()` returns the model or `NULL`; `find_many()` returns a collection:

```php
$user = User::find(1);
$users = User::find_many([1, 2, 3]);
```

<a id="find-or-fail"></a>
<a id="first-or-fail"></a>
<a id="not-found-exceptions"></a>

### Not Found Exceptions

`find_or_fail()` and `first_or_fail()` throw `System\Exceptions\ModelNotFoundException` when nothing is found:

```php
try {
    $user = User::find_or_fail($id);
    $user = User::where('email', '=', $email)->first_or_fail();
} catch (\System\Exceptions\ModelNotFoundException $e) {
    return Response::error('404');
}
```

<a id="query-builder-in-models"></a>
<a id="aggregation"></a>
<a id="building-queries"></a>

### Building Queries

Every [query builder](/docs/database/magic) method is available on a model, including dynamic wheres and aggregates:

```php
$users = User::where('active', '=', 1)
    ->where('votes', '>', 50)
    ->order_by('created_at', 'desc')
    ->skip(20)
    ->take(10)
    ->get();

$user = User::where_email($email)->first();
$users = User::where_email_and_active($email, 1)->get();
$users = User::where_in('id', [1, 2, 3])->get();

$count = User::where('active', '=', 1)->count();
$max = User::max('votes'); // also min(), avg(), sum()
```

`when()` applies a callback only when the value is truthy (`unless()` is the opposite):

```php
$users = User::when($role, function ($query, $role) {
    return $query->where('role', '=', $role);
})->get();
```

`sole()` returns exactly one model; it throws `ModelNotFoundException` if there is none and an `Exception` if there is more than one:

```php
$user = User::where('email', '=', 'budi@site.com')->sole();
```

<a id="chunking--lazy-iteration"></a>
<a id="chunking-results"></a>

### Chunking & Lazy Iteration

Process large tables without loading everything at once. These work like their
[query builder counterparts](/docs/database/magic#chunking--lazy-iteration), but hand back models.
Return `false` from a callback to stop:

```php
User::chunk(100, function ($users, $page) {
    foreach ($users as $user) {
        // ..
    }
});

User::each(function ($user, $key) {
    // one model at a time, fetched in chunks of 1000
});

// Page by the key: safe when the callback updates the rows.
User::where('active', '=', 0)->chunk_by_id(100, function ($users) {
    foreach ($users as $user) {
        $user->active = 1;
        $user->save();
    }
});

User::each_by_id(function ($user, $key) {
    // ..
});

foreach (User::with('posts')->lazy(500) as $user) {
    // fetched (and eager loaded) 500 at a time
}

foreach (User::with('posts')->lazy_by_id() as $user) {
    // same, paged by the key
}

foreach (User::where('active', '=', 1)->cursor() as $user) {
    // one query, one model at a time; with() is ignored
}
```

`chunk_by_id()`, `chunk_by_id_desc()`, `each_by_id()`, `lazy_by_id()` and `lazy_by_id_desc()`
page by the model key, qualified with its table (`users.id`), unless you pass a `$column`.
`lazy()` orders by the key when the query has no `order_by()`.

| Method | Queries | Eager loading | Use it when |
| --- | --- | --- | --- |
| `chunk()`, `each()`, `lazy()` | one per chunk | yes | you need batches with relationships |
| `chunk_by_id()`, `each_by_id()`, `lazy_by_id()` (and `_desc`) | one per chunk | yes | you update the models while iterating |
| `cursor()` | one | no | you want the least memory per model |

> `cursor()` hydrates each row as it is fetched, so it can not eager load: `with()` is ignored, use `lazy()` instead. On MySQL the whole result set is still buffered by `pdo_mysql` by default. On PHP 5.4 these methods return plain `Iterator`s (`System\Database\Query\Lazy` and `System\Database\Query\Cursor`) instead of generators; use `iterator_to_array()` if you need an array.

<a id="inserting--updating-models"></a>

## Inserting & Updating Models

<a id="inserting-models"></a>
<a id="create-method"></a>
<a id="save-method"></a>
<a id="inserting"></a>

### Inserting

Set attributes and call `save()`, or use `create()` to fill (respecting [mass assignment](#mass-assignment)) and save in one step. `create()` returns the model, or `false` on failure:

```php
$user = new User;
$user->name = 'Jane Doe';
$user->email = 'jane@example.com';
$user->save();

$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

echo $user->id;
```

<a id="updating-models"></a>
<a id="update-via-instance"></a>
<a id="update-via-static-method"></a>
<a id="mass-update"></a>
<a id="updating"></a>

### Updating

Change attributes on a retrieved model (directly or with `fill()`) and call `save()`. Only changed columns are written, and `updated_at` is refreshed:

```php
$user = User::find(1);
$user->email = 'new@example.com';
$user->save();

$user->fill(['name' => 'New Name'])->save();
```

`User::update($id, $attributes)` updates by primary key without loading the model and returns the affected row count. A query can also update many rows at once:

```php
User::update(1, ['name' => 'Updated Name']);

User::where('active', '=', 0)->update(['status' => 'inactive']);
```

> Query-level `update()` and `delete()` fire no [model events](#model-events). `update()` (and `increment()` / `decrement()`) sets `updated_at` (when the model uses [timestamps](#timestamps)) unless you pass it. `delete()` soft deletes when the model uses [soft deletes](#soft-deleting); `force_delete()` always removes the rows. Both return the affected row count.

<a id="retrieving-or-creating"></a>

### Retrieving or Creating

The first array is matched against the table; the second supplies extra values when creating (or the values to update):

```php
$user = User::first_or_new(['email' => 'budi@site.com']);                    // unsaved if missing
$user = User::first_or_create(['email' => 'budi@site.com'], ['name' => 'Budi']);
$user = User::update_or_create(['email' => 'budi@site.com'], ['name' => 'Budi']);
```

<a id="deleting-models"></a>
<a id="delete-via-instance"></a>
<a id="delete-via-static-method"></a>

## Deleting Models

`delete()` returns `true` when the record was deleted and `false` when the model doesn't exist in the database. `destroy()` deletes by primary key(s) and returns how many were deleted:

```php
$user = User::find(1);
$user->delete();

User::destroy(1);
User::destroy([1, 2, 3]);

User::where('active', '=', 0)->delete();       // bulk, see the note above
User::where('active', '=', 0)->force_delete(); // bulk, always permanent
```

<a id="soft-delete"></a>
<a id="soft-deleting"></a>

### Soft Deleting

With `$soft_delete = true` and a nullable `deleted_at` column, `delete()` sets `deleted_at` instead of removing the row, and queries automatically skip deleted rows:

```php
class User extends Facile
{
    public static $soft_delete = true;
}

// migration: $table->timestamp('deleted_at')->nullable();

$user->delete();                         // sets deleted_at
$user->trashed();                        // true

$users = User::all();                    // WHERE deleted_at IS NULL
$users = User::with_trashed()->get();    // include deleted
$users = User::only_trashed()->get();    // only deleted

$user = User::with_trashed()->find(1);
$user->restore();
$user->force_delete();                   // remove permanently

User::where('banned', '=', 1)->delete();               // soft deletes every match
User::only_trashed()->force_delete();                  // purge deleted rows
```

<a id="query-scopes"></a>

## Query Scopes

<a id="global-scopes"></a>

### Global Scopes

A global scope adds a constraint to every query of a model. Register it in [`boot()`](#booting) with a name and a Closure (or an object with an `apply($query, $model)` method):

```php
class User extends Facile
{
    protected static function boot()
    {
        static::add_global_scope('active', function ($query) {
            $query->where('active', '=', 1);
        });
    }
}

$users = User::all(); // WHERE active = 1

User::remove_global_scope('active'); // for every query that follows
```

Soft deleting is applied the same way: `with_trashed()` and `only_trashed()` lift the `deleted_at IS NULL` constraint.

<a id="local-scopes"></a>

### Local Scopes

A method prefixed with `scope_` can be called statically without the prefix. It receives the query plus any arguments and returns the query:

```php
class User extends Facile
{
    public function scope_role($query, $role)
    {
        return $query->where('role', '=', $role);
    }
}

$admins = User::role('admin')->order_by('name')->get();
$admins = User::where('active', '=', 1)->role('admin')->verified()->get(); // scopes chain anywhere
```

<a id="timestamps"></a>

## Timestamps

When `$timestamps` is `true` (the default), `save()` fills `created_at` on insert and `updated_at` on every save, using `$date_format`. Set it to `false` for tables without those columns:

```php
class Log extends Facile
{
    public static $timestamps = false;
}
```

<a id="mass-assignment"></a>

## Mass Assignment

`fill()`, `create()` and the constructor set many attributes at once. Since that data often comes straight from a request, **a model accepts nothing until it declares what it accepts**: `$guarded` defaults to `['*']`, so a model with neither `$fillable` nor `$guarded` ignores every attribute:

```php
class User extends Facile
{
}

$user = new User(Input::all()); // $user->attributes is empty
```

<a id="fillable"></a>

### Fillable

List the attributes that may be mass-assigned. This is the recommended approach, and once `$fillable` is set, `$guarded` is ignored:

```php
class User extends Facile
{
    public static $fillable = ['name', 'email', 'password'];
}

$user = User::create(Input::all()); // 'role' is dropped even if sent
```

<a id="guarded"></a>

### Guarded

Alternatively, leave `$fillable` unset and list the attributes to block. This is weaker: any column added later is immediately mass-assignable.

```php
class User extends Facile
{
    public static $guarded = ['id', 'is_admin'];
}
```

`fill_raw()` bypasses both lists.

> **Upgrading:** `$guarded` used to default to `[]` (everything assignable). Models that declare `$fillable` are unaffected; models that declare neither now accept nothing and need a `$fillable`.

<a id="relationships"></a>

## Relationships

Define a relationship as a method returning a relationship object. Read it as a property (`$user->posts`) to get the results, or call it as a method (`$user->posts()`) to keep building the query.

<a id="one-to-one"></a>

### One To One

`has_one()` expects a `user_id` foreign key on `phones` (the lowercase parent class name + `_id`). The inverse, `belongs_to()`, derives its foreign key from the **method name** + `_id`, so `user()` uses `user_id`. Pass the foreign key as the second argument to override either:

```php
class User extends Facile
{
    public function phone()
    {
        return $this->has_one('Phone'); // or has_one('Phone', 'custom_user_id')
    }
}

class Phone extends Facile
{
    public function user()
    {
        return $this->belongs_to('User');
    }
}

echo User::find(1)->phone->number;
echo Phone::find(1)->user->name;
```

<a id="one-to-many"></a>

### One To Many

```php
class User extends Facile
{
    public function posts()
    {
        return $this->has_many('Post'); // posts.user_id
    }
}

foreach ($user->posts as $post) {
    echo $post->title;
}

$posts = $user->posts()->where('published', '=', 1)->get();
```

The inverse is `belongs_to('User')` on `Post`, as above.

<a id="many-to-many"></a>

### Many To Many

`belongs_to_many()` uses a pivot table named after both models in alphabetical order (`role_user`, with `user_id` and `role_id`). Pass the table and the two keys to override:

```php
class User extends Facile
{
    public function roles()
    {
        return $this->belongs_to_many('Role');
        // or belongs_to_many('Role', 'user_roles', 'user_id', 'role_id')
    }
}

foreach ($user->roles as $role) {
    echo $role->name;
}
```

Define `belongs_to_many('User')` on `Role` for the inverse.

<a id="has-many-through"></a>

### Has Many Through

Reach distant records through an intermediate model, e.g. a country's posts through its users (`users.country_id`, `posts.user_id`):

```php
class Country extends Facile
{
    public function posts()
    {
        return $this->has_many_through('Post', 'User');
    }
}

$posts = Country::find(1)->posts;
```

The keys can be overridden: `has_many_through($model, $through, $first_key, $second_key, $local_key, $second_local_key)`.

<a id="polymorphic-relations"></a>

### Polymorphic Relations

A polymorphic relation lets one model belong to several others through a `{name}_id` and `{name}_type` column pair. Use `morph_many()` (or `morph_one()` for a single record) on the parents and `morph_to()` on the child:

```php
// comments: id, commentable_id, commentable_type, content

class Post extends Facile
{
    public function comments()
    {
        return $this->morph_many('Comment', 'commentable');
    }
}

class Comment extends Facile
{
    public function commentable()
    {
        return $this->morph_to('commentable');
    }
}

$comments = Post::find(1)->comments;
$parent = Comment::find(1)->commentable; // a Post, Video, ...
```

The `{name}_type` column stores the parent's class name. `morph_to()` only turns it back into a
Facile model: a value naming any other class, or no class, gives `null`, and that class is never
loaded nor created.

To store short names instead, independent of the class names, register a morph map once, in
`application/boot.php`. Parents are then stored under their alias, and the aliases resolve back:

```php
Facile::morph_map([
    'post' => 'Post',
    'video' => 'Video',
]);
```

Map every model before rows are written with the alias; existing rows that hold a class name keep
working.

<a id="relationship-queries"></a>
<a id="querying-relationships"></a>

## Querying Relationships

Filter parent models by their relationships without loading them. Each method compiles into a correlated subquery, so no extra round trip is made:

```php
User::has('posts')->get();                 // at least one post
User::has('posts', '>=', 3)->get();        // at least three
User::doesnt_have('posts')->get();         // none (alias: where_doesnt_have)

User::where_has('posts', function ($query) {
    $query->where('published', '=', 1);
})->get();

User::where_relation('posts', 'published', '=', 1)->get(); // single condition
User::where('role', '=', 'admin')
    ->or_where_relation('posts', 'title', 'Hello World')   // operator optional, OR-joined
    ->get();

foreach (User::with_count('posts')->get() as $user) {
    echo $user->posts_count;
}
```

The other aggregates of a related column work the same way, and take a closure to constrain them:

```php
$users = User::with_sum('posts', 'votes')   // posts_sum_votes
    ->with_avg('posts', 'votes')            // posts_avg_votes
    ->with_min('posts', 'votes')            // posts_min_votes
    ->with_max('posts', 'votes')            // posts_max_votes
    ->with_exists('posts')                  // posts_exists: 1 or 0
    ->get();

User::with_sum(['posts' => function ($query) {
    $query->where('published', '=', 1);
}], 'votes')->get();
```

A sum, average, minimum or maximum over no related row is `null`.

```sql
SELECT * FROM "users" WHERE EXISTS (
    SELECT 1 FROM "posts" WHERE "posts"."user_id" = "users"."id"
)
```

`or_has()` is the OR-joined form of `has()`. These work with `has_one`, `has_many`, `belongs_to`, `belongs_to_many`, `morph_one`, `morph_many` and `has_many_through`; `morph_to` and `morph_to_many` throw an exception because they can't be correlated with a single table.

`where_belongs_to()` filters by a parent model (or a collection of them), reading the foreign key from the `belongs_to` relationship. The relationship name is guessed from the parent's class (`BlogAuthor` → `blog_author()`); pass it as the second argument otherwise:

```php
$posts = Post::where_belongs_to($user)->get();
// WHERE "posts"."user_id" = ?

$posts = Post::where_belongs_to(User::where('active', '=', 1)->get())->get();
// WHERE "posts"."user_id" IN (?, ?, ?)

$posts = Post::where_belongs_to($user, 'author')->get();
$posts = Post::where('featured', '=', 1)->or_where_belongs_to($user, 'author')->get();
```

An exception is thrown when the relationship doesn't exist, isn't a `belongs_to`, or points to a different model.

<a id="inserting-related-models"></a>

## Inserting Related Models

On `has_one` / `has_many`, `insert()` takes an array or a model, sets the foreign key and saves it. `save()` does the same for one model or an array of them (inserting or updating each according to whether it exists) and returns `false` if any save fails:

```php
$post = $user->posts()->insert(['title' => 'New Post']);

$post = new Post;
$post->title = 'Another Post';
$user->posts()->save($post);

$user->profile()->save(new Profile(['bio' => 'Hello'])); // has_one
```

On `belongs_to_many`, manage the pivot rows. `attach()` and `detach()` accept an id or a model (`detach()` also an array):

```php
$user->roles()->attach($role_id);
$user->roles()->detach($role_id);
$user->roles()->sync([1, 2, 3]);   // attach missing, detach the rest
$user->roles()->sync([1, 2 => ['level' => 'lead']]); // with pivot attributes
$user->roles()->sync_without_detaching([4]);          // attach only
$user->roles()->toggle([1, 5]);    // detach the attached ones, attach the others
$user->roles()->update_existing_pivot(2, ['level' => 'senior']);
$user->roles()->delete();          // detach all

$user->roles()->insert(['name' => 'Editor']); // create the role and attach it
```

`sync()`, `sync_without_detaching()` and `toggle()` return the IDs they changed, as
`['attached' => [...], 'detached' => [...], 'updated' => [...]]`, and attach in one `INSERT`.

<a id="working-with-pivot-tables"></a>
<a id="pivot-tables"></a>

### Pivot Tables

The pivot table only needs the two key columns. Extra columns are read only when requested with `with()`, and are available on `->pivot`; write them with the second argument of `attach()`:

```php
foreach ($user->roles()->with(['expires_at', 'created_by'])->get() as $role) {
    echo $role->pivot->expires_at;
}

$user->roles()->attach($role_id, [
    'expires_at' => '2024-12-31',
    'created_by' => Auth::user()->id,
]);
```

> To have `attach()` fill `created_at`/`updated_at` on every pivot table, set `System\Database\Facile\Pivot::$timestamps = true` in `application/boot.php`. The columns must exist.

<a id="eager-loading"></a>

## Eager Loading

Reading a relationship inside a loop runs one query per row (the "N+1" problem). `with()` loads it for all rows up front in one extra query:

```php
$posts = Post::with('user')->get(); // 2 queries in total

foreach ($posts as $post) {
    echo $post->user->name;
}

$posts = Post::with(['user', 'comments.user'])->get(); // several and nested
```

A nested relation loads its parents too: `with('comments.user')` loads `comments`, then their
`user`. Relations listed in the model's `$with` property are always eager loaded.

Models already fetched can load relations afterwards, in one query per relation for the whole
collection. `load_missing()` skips what is loaded already:

```php
$user = User::find(1)->load('posts');

$users = User::where('active', '=', 1)->get();
$users->load(['posts', 'roles']);
$users->load_missing('posts.comments');
```

To catch N+1 queries during development, make lazy loading throw a
`System\Exceptions\LazyLoadingException` for models fetched together with others. A single
model, such as `User::find(1)->posts`, still loads lazily:

```php
// application/boot.php
if (! Debugger::$productionMode) {
    Facile::prevent_lazy_loading();
}
```

> When all parent keys are integers they are written straight into the SQL (`IN (1, 2, 3)`) instead of being bound, so large result sets don't hit the driver's bound-parameter limit. Whether keys come back as integers depends on the PDO driver and PHP version (SQLite only on PHP 8.1+, SQL Server returns strings by default). String keys and polymorphic id columns always use bound parameters.

<a id="constraining-eager-loading"></a>
<a id="constraining-eager-loads"></a>

### Constraining Eager Loads

Pass a Closure as the value to add constraints to the eager load query:

```php
$users = User::with([
    'posts' => function ($query) {
        $query->where('published', '=', 1)->order_by('created_at', 'desc');
    },
    'comments',
])->get();
```

<a id="accessor--mutator"></a>
<a id="accessor"></a>
<a id="mutator"></a>
<a id="accessors--mutators"></a>

## Accessors & Mutators

A `get_{attribute}` method runs when the attribute is read and receives the raw value; it can also define a computed attribute. A `set_{attribute}` method runs when the attribute is assigned and must store the value with `set_attribute()`:

```php
class User extends Facile
{
    public function get_name($value)
    {
        return ucwords($value);
    }

    public function get_full_name()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function set_password($value)
    {
        $this->set_attribute('password', Hash::make($value));
    }
}

echo $user->full_name;
$user->password = 'secret123'; // stored hashed
```

<a id="attribute-casting"></a>

## Attribute Casting

`$casts` converts raw database values to native PHP types on read and serializes them back on write. It also evens out driver differences (MySQL returns every column as a string, SQLite does not):

```php
class Post extends Facile
{
    public static $casts = [
        'active' => 'boolean',
        'views' => 'integer',
        'price' => 'decimal:2',
        'options' => 'array',
        'tags' => 'collection',
        'published_at' => 'datetime',
    ];
}

$post->active;       // true, not '1'
$post->options;      // ['a' => 1], not '{"a":1}'
$post->published_at; // a Carbon instance
$post->price;        // '10.50'

$post->options = ['a' => 2]; // encoded back to JSON
```

Supported types: `int`, `integer`, `real`, `float`, `double`, `decimal:<digits>`, `string`, `bool`, `boolean`, `object`, `array`, `json`, `collection`, `date`, `datetime` and `timestamp`.

<a id="hidden-attributes"></a>
<a id="converting-to-array--json"></a>
<a id="serialization"></a>

## Serialization

`to_array()`, `to_json()` and `json_encode($model)` convert a model, including loaded relationships and casts (dates are formatted with `$date_format`):

```php
$array = User::with('posts')->find(1)->to_array();
// ['id' => 1, 'name' => 'John', 'posts' => [['id' => 1, 'title' => 'Post 1'], ..]]
```

`$hidden` lists attributes to leave out; `$visible`, when not empty, is a whitelist that wins over `$hidden`. `$appends` adds accessors to the output:

```php
class User extends Facile
{
    public static $hidden = ['password', 'remember_token'];
    public static $appends = ['full_name'];
}
```

Override them for a single instance:

```php
$user->make_visible('password')->to_array();
$user->make_hidden('email')->to_array();
$user->append('full_name')->to_array();
```

<a id="model-validation"></a>
<a id="validation"></a>

## Validation

Define `$rules` (and optionally `$messages`), then call `is_valid()`. The validator is available in `$model->validation`:

```php
class User extends Facile
{
    public static $rules = [
        'name' => 'required|min:3|max:100',
        'email' => 'required|email|unique:users',
    ];

    public static $messages = [
        'required' => ':attribute is required',
    ];
}

$user = new User(Input::all()); // or $user->fill(...) on an existing model

if (! $user->is_valid()) {
    return Redirect::back()->with_input()->with_errors($user->validation);
}

$user->save();
```

<a id="model-events"></a>

## Model Events

A model fires an event at each step of its life:

| Event | When |
| --- | --- |
| `retrieved` | A row has just been read into a model |
| `saving` / `saved` | Before / after any save |
| `creating` / `created` | Before / after an insert |
| `updating` / `updated` | Before / after an update |
| `deleting` / `deleted` | Before / after a delete |
| `restoring` / `restored` | Before / after a soft-deleted row is restored |
| `replicating` | On the copy made by `replicate()` |

Listen by calling the event name statically with a Closure that receives the model:

```php
User::creating(function ($user) {
    $user->uuid = Str::uuid();
});
```

Underneath these are [hooks](/docs/hooks), so you can also listen to one model or to every model:

```php
Hook::listen('facile.creating: User', function ($user) { /* .. */ });
Hook::listen('facile.creating', function ($model) { /* every model */ });
```

> `retrieved` fires once per row a query returns. Events nobody listens to cost nothing.

<a id="calling-an-operation-off"></a>
<a id="cancelling-an-operation"></a>

### Cancelling an Operation

Return exactly `false` from a `saving`, `creating`, `updating`, `deleting` or `restoring` listener to cancel the operation: nothing is written and `save()` / `delete()` return `false`. All listeners still run:

```php
User::deleting(function ($user) {
    if ($user->is_admin) {
        return false;
    }
});

$admin->delete(); // false, row still there
```

<a id="booting"></a>

### Booting

`boot()` runs once, the first time a model of that class is instantiated, and is the place for a model to register its own listeners and global scopes:

```php
class User extends Facile
{
    protected static function boot()
    {
        static::creating(function ($user) {
            $user->uuid = Str::uuid();
        });
    }
}
```

<a id="observers"></a>

## Observers

An observer groups a model's listeners into one class: each method named after an event is registered, other methods are ignored. Generate one with `php rakit make:observer user`; it lives in `application/observers/` and is named after the file with an `_Observer` suffix (package observers carry the package prefix, e.g. `Blog_Post_Observer` in `packages/blog/observers/post.php`):

```php
// application/observers/user.php

class User_Observer
{
    public function creating($user)
    {
        $user->uuid = Str::uuid();
    }

    public function deleted($user)
    {
        Log::info('User deleted: ' . $user->id);
    }
}
```

Register it (by class name or instance) while the application boots. Returning `false` cancels an operation just like a Closure listener:

```php
// application/boot.php

User::observe('User_Observer'); // or User::observe(new User_Observer())
```

<a id="model-helpers"></a>

## Model Helpers

```php
// Where on the primary key (accepts an array too)
User::where_key(1)->first();
User::where_key_not(1)->get();

// Reload this instance from the database (fresh() returns a new instance instead)
$user->refresh();

// Unsaved copy without the primary key and timestamps
$copy = $user->replicate();

// Same record?
$user->is($other);
$user->is_not($other);

// Update only the timestamps
$user->touch();

// Increment / decrement a column of this record
$post->increment('views');
$post->decrement('stock', 2);

// What changed on the last save()?
$user->was_changed();
$user->was_changed('email');
$user->get_original('email');
```
