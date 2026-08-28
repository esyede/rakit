# Facile Model

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Conventions](#conventions)
- [Model Configuration](#model-configuration)
  - [Available Properties](#available-properties)
- [Retrieving Models](#retrieving-models)
  - [Retrieving All Records](#retrieving-all-records)
  - [Retrieving by Primary Key](#retrieving-by-primary-key)
  - [Find Or Fail](#find-or-fail)
  - [First Or Fail](#first-or-fail)
- [Query Builder in Models](#query-builder-in-models)
- [Aggregation](#aggregation)
- [Inserting Models](#inserting-models)
  - [Create Method](#create-method)
  - [Save Method](#save-method)
- [Updating Models](#updating-models)
  - [Update Via Instance](#update-via-instance)
  - [Update Via Static Method](#update-via-static-method)
  - [Mass Update](#mass-update)
- [Deleting Models](#deleting-models)
  - [Delete Via Instance](#delete-via-instance)
  - [Delete Via Static Method](#delete-via-static-method)
  - [Soft Delete](#soft-delete)
- [Timestamps](#timestamps)
- [Mass Assignment](#mass-assignment)
  - [Fillable](#fillable)
  - [Guarded](#guarded)
- [Model Validation](#model-validation)
- [Accessor & Mutator](#accessor--mutator)
  - [Accessor](#accessor)
  - [Mutator](#mutator)
- [Hidden Attributes](#hidden-attributes)
- [Attribute Casting](#attribute-casting)
- [Model Helpers](#model-helpers)
- [Relationship Queries](#relationship-queries)
- [Relationships](#relationships)
  - [One To One](#one-to-one)
  - [One To Many](#one-to-many)
  - [Many To Many](#many-to-many)
  - [Has Many Through](#has-many-through)
  - [Polymorphic Relations](#polymorphic-relations)
- [Eager Loading](#eager-loading)
- [Constraining Eager Loading](#constraining-eager-loading)
- [Inserting Related Models](#inserting-related-models)
- [Working with Pivot Tables](#working-with-pivot-tables)
- [Converting to Array & JSON](#converting-to-array--json)
- [Global Scopes](#global-scopes)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

Facile is Rakit's ORM. Each database table is mapped to a model class, and
each row to an instance of that class. You can query, insert, update, and
delete records by calling methods on the model rather than writing SQL.

A bare-bones model is just a class extending `Facile`:

```php
<?php

class User extends Facile
{
    // Conventions take care of everything by default.
}
```

By convention this model talks to the `users` table — see
[Conventions](#conventions) below for the rules and how to override them.

<a id="conventions"></a>
## Conventions

Facile follows several conventions:

- **Table name**: Plural form (plural) of the model name in lowercase with underscores. Example: `User` model → `users` table, `OrderItem` model → `order_items` table
- **Primary key**: Column named `id` with auto-increment
- **Timestamps**: `created_at` and `updated_at` columns (optional)
- **Soft deletes**: `deleted_at` column (optional)

You can override these conventions through static properties in the model.

<a id="model-configuration"></a>
## Model Configuration

<a id="available-properties"></a>
### Available Properties

```php
<?php

class User extends Facile
{
    // Table name (default: plural of class name)
    public static $table = 'my_users';

    // Primary key column (default: 'id')
    public static $key = 'user_id';

    // Database connection (default: default connection)
    public static $connection = 'mysql';

    // Sequence name for PostgreSQL (default: null)
    public static $sequence = 'users_id_seq';

    // Use created_at and updated_at timestamps (default: true)
    public static $timestamps = true;

    // Number of items per page for pagination (default: 20)
    public static $perpage = 15;

    // Enable soft deletes (default: false)
    public static $soft_delete = false;

    // Attributes allowed for mass-assignment (default: null = all)
    public static $fillable = ['name', 'email', 'password'];

    // Attributes not allowed for mass-assignment (default: [])
    public static $guarded = ['id', 'password'];

    // Attributes hidden when to_array() or to_json() (default: [])
    public static $hidden = ['password', 'remember_token'];

    // Whitelist of attributes, wins over $hidden when it is not empty (default: [])
    public static $visible = [];

    // Accessors appended to to_array() and to_json() (default: [])
    public static $appends = ['full_name'];

    // Attributes cast to a native type (default: [])
    public static $casts = [
        'active' => 'boolean',
        'options' => 'array',
        'price' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    // Format used when a date-ish attribute is serialized (default: 'Y-m-d H:i:s')
    public static $date_format = 'Y-m-d H:i:s';

    // Relations to eager load (default: [])
    public $with = ['posts', 'profile'];

    // Validation rules (default: [])
    public static $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email|unique:users',
    ];

    // Custom validation messages (default: [])
    public static $messages = [
        'required' => ':attribute is required',
    ];
}
```

<a id="retrieving-models"></a>
## Retrieving Models

<a id="retrieving-all-records"></a>
### Retrieving All Records

```php
// Retrieve all users
$users = User::all();

foreach ($users as $user) {
    echo $user->name;
}
```

`all()`, `get()` and the results of `paginate()` all hand back a `Collection` of models, so
the whole collection API is available right away:

```php
$names = User::all()->pluck('name');
$grouped = User::all()->group_by('role');
$active = User::where('active', '=', 1)->get()->filter(function ($user) {
    return $user->is_admin();
});
```

It stays countable, iterable and array accessible, so `count($users)`,
`foreach ($users as $user)` and `$users[0]` keep working the way they always did.

<a id="retrieving-by-primary-key"></a>
### Retrieving by Primary Key

```php
// Find user with id = 1
$user = User::find(1);

if ($user) {
    echo $user->email;
}

// Find users with ids in array
$users = User::find([1, 2, 3]);
```

<a id="find-or-fail"></a>
### Find Or Fail

Method that throws exception if not found:

```php
try {
    $user = User::find_or_fail($id);
    echo $user->name;
} catch (ModelNotFoundException $e) {
    return Response::error('404');
}
```

<a id="first-or-fail"></a>
### First Or Fail

```php
try {
    $user = User::where('email', '=', $email)->first_or_fail();
} catch (ModelNotFoundException $e) {
    return Redirect::back()->with('error', 'User not found');
}
```

<a id="query-builder-in-models"></a>
## Query Builder in Models

**All Magic Query Builder methods can be used in Models:**

```php
// WHERE clause
$users = User::where('votes', '>', 100)->get();

$user = User::where('email', '=', $email)->first();

// Dynamic WHERE
$user = User::where_email($email)->first();

$users = User::where_email_and_active($email, 1)->get();

// WHERE IN
$users = User::where_in('id', [1, 2, 3])->get();

// WHERE NULL
$users = User::where_null('deleted_at')->get();

// WHERE BETWEEN
$users = User::where_between('age', 18, 30)->get();

// ORDER BY
$users = User::order_by('created_at', 'desc')->get();

// LIMIT
$users = User::take(10)->get();

// OFFSET
$users = User::skip(20)->take(10)->get();

// Combination
$users = User::where('active', '=', 1)
    ->where('votes', '>', 50)
    ->order_by('created_at', 'desc')
    ->take(20)
    ->get();
```

<a id="aggregation"></a>
## Aggregation

```php
// COUNT
$count = User::count();
$active_count = User::where('active', '=', 1)->count();

// MAX
$max_votes = User::max('votes');

// MIN
$min_age = User::min('age');

// AVG
$avg_salary = User::avg('salary');

// SUM
$total_votes = User::sum('votes');
```

<a id="inserting-models"></a>
## Inserting Models

<a id="create-method"></a>
### Create Method

The `create()` method creates and saves the model in one step:

```php
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('secret'),
]);

if ($user) {
    echo 'User created with ID: ' . $user->id;
}
```

<a id="save-method"></a>
### Save Method

```php
$user = new User;
$user->name = 'Jane Doe';
$user->email = 'jane@example.com';
$user->password = Hash::make('secret');

if ($user->save()) {
    echo 'User saved with ID: ' . $user->id;
}
```

**Automatic timestamps:**

If `$timestamps = true`, the `created_at` and `updated_at` columns are filled automatically.

<a id="updating-models"></a>
## Updating Models

<a id="update-via-instance"></a>
### Update Via Instance

```php
$user = User::find(1);
$user->email = 'newemail@example.com';
$user->save();

// Or with fill
$user = User::find(1);
$user->fill([
    'name' => 'New Name',
    'email' => 'newemail@example.com',
]);
$user->save();
```

<a id="update-via-static-method"></a>
### Update Via Static Method

```php
// Update by ID
$affected = User::update(1, [
    'email' => 'updated@example.com',
    'name' => 'Updated Name',
]);
```

<a id="mass-update"></a>
### Mass Update

```php
// Update multiple records
User::where('active', '=', 0)
    ->where('created_at', '<', '2020-01-01')
    ->update(['status' => 'inactive']);
```

<a id="deleting-models"></a>
## Deleting Models

<a id="delete-via-instance"></a>
### Delete Via Instance

```php
$user = User::find(1);

if ($user->delete()) {
    echo 'User deleted';
}
```

> **Note:** `delete()` returns a boolean, `true` when the record was deleted and `false` when
> the model does not exist in the database yet.


<a id="delete-via-static-method"></a>
### Delete Via Static Method

```php
// Delete by ID
User::delete(1);

// Delete multiple
User::where('active', '=', 0)->delete();
```

<a id="soft-delete"></a>
### Soft Delete

Enable soft delete in the model:

```php
class User extends Facile
{
    public static $soft_delete = true;
}
```

Make sure the table has a `deleted_at` column:

```php
// In migration
$table->timestamp('deleted_at')->nullable();
```

Now `delete()` will set `deleted_at` instead of deleting the record:

```php
$user = User::find(1);
$user->delete(); // Set deleted_at = now()

// Query only retrieves non-deleted records
$users = User::all(); // Automatically WHERE deleted_at IS NULL

// Include deleted records
$users = User::with_trashed()->get();

// Only deleted records
$users = User::only_trashed()->get();

// Restore soft deleted record
$user = User::with_trashed()->find(1);
$user->restore();

// Force delete (permanently delete)
$user->force_delete();
```

<a id="timestamps"></a>
## Timestamps

By default, Facile will automatically fill `created_at` and `updated_at`:

```php
class Post extends Facile
{
    public static $timestamps = true; // Default
}

$post = new Post;
$post->title = 'My Post';
$post->save();
// created_at and updated_at filled automatically

$post->title = 'Updated Title';
$post->save();
// updated_at updated automatically
```

Disable timestamps:

```php
class Log extends Facile
{
    public static $timestamps = false;
}
```

<a id="mass-assignment"></a>
## Mass Assignment

Mass assignment allows you to fill multiple attributes at once.

<a id="fillable"></a>
### Fillable

Whitelist attributes allowed for mass-assignment:

```php
class User extends Facile
{
    public static $fillable = ['name', 'email', 'password'];
}

// Only name, email, password will be filled
$user = User::create(Input::all());

// role will not be filled even if in input
// because not in $fillable
```

<a id="guarded"></a>
### Guarded

Blacklist attributes not allowed for mass-assignment:

```php
class User extends Facile
{
    public static $guarded = ['id', 'is_admin'];
}

// All attributes can be filled except id and is_admin
$user = User::create(Input::all());
```

Use `'*'` to close everything and open the model up through `$fillable` alone:

```php
class User extends Facile
{
    public static $guarded = ['*'];
    public static $fillable = ['name', 'email'];
}
```

> **Note:** If `$fillable` is not set, all attributes can be mass-assigned (except those in `$guarded`).
> That default is permissive on purpose, but it does mean `Model::create(Input::all())` will
> happily write any column the request names. Declare `$fillable`, or guard with `'*'`, on any
> model that holds something a visitor should not be able to set.

<a id="model-validation"></a>
## Model Validation

Facile supports built-in validation:

```php
class User extends Facile
{
    public static $rules = [
        'name' => 'required|min:3|max:100',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8',
    ];

    public static $messages = [
        'required' => ':attribute is required',
        'email' => ':attribute must be a valid email',
    ];
}

// In controller
$user = new User(Input::all());

if (!$user->is_valid()) {
    return Redirect::back()
        ->with_input()
        ->with_errors($user->validation);
}

$user->save();
```

**Validation during update:**

```php
$user = User::find(1);
$user->fill(Input::all());

if (!$user->is_valid()) {
    return Redirect::back()->with_errors($user->validation);
}

$user->save();
```

<a id="accessor--mutator"></a>
## Accessor & Mutator

<a id="accessor"></a>
### Accessor

Accessors change how attributes are read:

```php
class User extends Facile
{
    /**
     * Accessor for 'name' attribute
     * Automatically called when $user->name
     */
    public function get_name($value)
    {
        return ucwords($value);
    }

    /**
     * Accessor for 'email'
     */
    public function get_email($value)
    {
        return strtolower($value);
    }

    /**
     * Accessor for computed attribute
     */
    public function get_full_name()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}

$user = User::find(1);
echo $user->name; // Automatically ucwords()
echo $user->email; // Automatically strtolower()
echo $user->full_name; // first_name + last_name
```

<a id="mutator"></a>
### Mutator

Mutators change how attributes are written:

```php
class User extends Facile
{
    /**
     * Mutator for 'password'
     * Automatically called when $user->password = 'xxx'
     */
    public function set_password($value)
    {
        if (!empty($value)) {
            $this->set_attribute('password', Hash::make($value));
        }
    }

    /**
     * Mutator for 'email'
     */
    public function set_email($value)
    {
        $this->set_attribute('email', strtolower($value));
    }

    /**
     * Mutator for 'name'
     */
    public function set_name($value)
    {
        $this->set_attribute('name', ucwords($value));
    }
}

$user = new User;
$user->password = 'secret123'; // Automatically hashed
$user->email = 'USER@EXAMPLE.COM'; // Automatically lowercase
$user->name = 'john doe'; // Automatically ucwords
$user->save();
```

<a id="hidden-attributes"></a>
## Hidden Attributes

Hide attributes when converting to array or JSON:

```php
class User extends Facile
{
    public static $hidden = ['password', 'remember_token'];
}

$user = User::find(1);

// password and remember_token do not appear
$array = $user->to_array();
$json = $user->to_json();

echo json_encode($user); // Same as to_json()
```

Instead of listing what to hide, you can list what to keep with `$visible`. When it is not
empty it wins over `$hidden`:

```php
class User extends Facile
{
    public static $visible = ['id', 'name'];
}
```

Both lists can be overridden for a single instance:

```php
$user->make_visible('password')->to_array();   // show it just this once
$user->make_hidden('email')->to_array();       // hide it just this once
$user->append('full_name')->to_array();        // append an accessor just this once
```

<a id="attribute-casting"></a>
## Attribute Casting

`$casts` turns raw database values into native PHP types when you read them, and serializes
them back when you write them. It also smooths over the differences between drivers, since
MySQL hands every column back as a string while SQLite does not.

```php
class Post extends Facile
{
    public static $casts = [
        'active' => 'boolean',
        'views' => 'integer',
        'rating' => 'float',
        'price' => 'decimal:2',
        'options' => 'array',
        'payload' => 'object',
        'tags' => 'collection',
        'published_at' => 'datetime',
        'expires_at' => 'date',
        'synced_at' => 'timestamp',
    ];
}

$post = Post::find(1);

$post->active;        // true, not 1 or '1'
$post->options;       // ['a' => 1], not '{"a":1}'
$post->published_at;  // a Carbon instance
$post->price;         // '10.50'

// Assigning an array is encoded back into JSON on its own
$post->options = ['a' => 2];
$post->save();
```

Supported types: `int`, `integer`, `real`, `float`, `double`, `decimal:<digits>`, `string`,
`bool`, `boolean`, `object`, `array`, `json`, `collection`, `date`, `datetime` and `timestamp`.

In `to_array()` and `to_json()`, a `datetime` cast is written back out using `$date_format`,
which defaults to `Y-m-d H:i:s`.

<a id="model-helpers"></a>
## Model Helpers

```php
// Find it or make an unsaved instance
$user = User::first_or_new(['email' => 'budi@site.com']);

// Find it or create it
$user = User::first_or_create(['email' => 'budi@site.com'], ['name' => 'Budi']);

// Update it or create it
$user = User::update_or_create(['email' => 'budi@site.com'], ['name' => 'Budi']);

// Several records at once
$users = User::find_many([1, 2, 3]);

// Delete by primary key, returns how many were deleted
User::destroy([1, 2]);

// Where on the primary key
User::where_key(1)->first();
User::where_key_not(1)->get();

// Reload this very instance from the database (fresh() returns a new one instead)
$user->refresh();

// An unsaved copy, without the primary key and the timestamps
$copy = $user->replicate();

// Is it the very same record?
$user->is($other);
$user->is_not($other);

// Touch the timestamps without changing anything else
$user->touch();

// Move a column of this very record
$post->increment('views');
$post->decrement('stock', 2);

// What changed on the last save()?
$user->was_changed();
$user->was_changed('email');
$user->get_original('email');

// One chunk at a time, models included
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // ..
    }
});

User::each(function ($user) {
    // ..
});

// Exactly one, complains when there is none or more than one
$user = User::where('email', '=', 'budi@site.com')->sole();

// Only apply the filter when it was actually asked for
$users = User::when($role, function ($query, $role) {
    return $query->where('role', '=', $role);
})->get();
```

<a id="relationship-queries"></a>
## Relationship Queries

Filter the parent models by what their relationship holds, without loading anything:

```php
// Users that own at least one post
$users = User::has('posts')->get();

// Users that own no post at all
$users = User::doesnt_have('posts')->get();

// Users that own at least three posts
$users = User::has('posts', '>=', 3)->get();

// Users that own at least one published post
$users = User::where_has('posts', function ($query) {
    $query->where('published', '=', 1);
})->get();

// Select the number of posts as a 'posts_count' column
foreach (User::with_count('posts')->get() as $user) {
    echo $user->name . ': ' . $user->posts_count;
}
```

All of them compile into a correlated subquery, so no extra round trip is made:

```sql
SELECT * FROM "users" WHERE EXISTS (
    SELECT 1 FROM "posts" WHERE "posts"."user_id" = "users"."id"
)
```

Supported for `has_one`, `has_many`, `belongs_to`, `belongs_to_many`, `morph_one`,
`morph_many` and `has_many_through`. The polymorphic `morph_to` and `morph_to_many` raise a
clear exception instead, because they cannot be correlated with a single table.

<a id="relationships"></a>
## Relationships

<a id="one-to-one"></a>
### One To One

**Setup:**

```php
// users table
// Columns: id, name, email

// phones table
// Columns: id, user_id, number
```

**Model:**

```php
class User extends Facile
{
    public function phone()
    {
        return $this->has_one('Phone');
    }
}

class Phone extends Facile
{
    public function user()
    {
        return $this->belongs_to('User');
    }
}
```

**Usage:**

```php
$user = User::find(1);
$phone = $user->phone; // Get phone belonging to user

echo $phone->number;

// Inverse relation
$phone = Phone::find(1);
$user = $phone->user; // Get user from phone

echo $user->name;
```

**Custom foreign key:**

```php
public function phone()
{
    return $this->has_one('Phone', 'custom_user_id');
}
```

<a id="one-to-many"></a>
### One To Many

**Setup:**

```php
// users table
// Columns: id, name

// posts table
// Columns: id, user_id, title, content
```

**Model:**

```php
class User extends Facile
{
    public function posts()
    {
        return $this->has_many('Post');
    }
}

class Post extends Facile
{
    public function user()
    {
        return $this->belongs_to('User');
    }
}
```

**Usage:**

```php
$user = User::find(1);
$posts = $user->posts; // Array of Post objects

foreach ($posts as $post) {
    echo $post->title;
}

// With query builder
$posts = $user->posts()->where('published', '=', 1)->get();

// Inverse
$post = Post::find(1);
$user = $post->user;
```

<a id="many-to-many"></a>
### Many To Many

**Setup:**

```php
// users table
// Columns: id, name

// roles table
// Columns: id, name

// role_user pivot table
// Columns: user_id, role_id
```

**Model:**

```php
class User extends Facile
{
    public function roles()
    {
        return $this->belongs_to_many('Role');
    }
}

class Role extends Facile
{
    public function users()
    {
        return $this->belongs_to_many('User');
    }
}
```

**Usage:**

```php
$user = User::find(1);
$roles = $user->roles;

foreach ($roles as $role) {
    echo $role->name;
}
```

**Custom pivot table:**

```php
public function roles()
{
    return $this->belongs_to_many('Role', 'user_roles', 'user_id', 'role_id');
}
```

<a id="has-many-through"></a>
### Has Many Through

**Setup:**

```php
// countries table
// Columns: id, name

// users table
// Columns: id, country_id, name

// posts table
// Columns: id, user_id, title
```

**Model:**

```php
class Country extends Facile
{
    public function posts()
    {
        return $this->has_many_through('Post', 'User');
    }
}
```

**Usage:**

```php
$country = Country::find(1);
$posts = $country->posts; // All posts from users in country
```

<a id="polymorphic-relations"></a>
### Polymorphic Relations

**Morph One / Many:**

```php
// posts table
// Columns: id, title

// videos table
// Columns: id, title

// comments table
// Columns: id, commentable_id, commentable_type, content
```

**Model:**

```php
class Post extends Facile
{
    public function comments()
    {
        return $this->morph_many('Comment', 'commentable');
    }
}

class Video extends Facile
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
```

**Usage:**

```php
$post = Post::find(1);
$comments = $post->comments;

$comment = Comment::find(1);
$parent = $comment->commentable; // Can be Post or Video
```

<a id="eager-loading"></a>
## Eager Loading

Solve N+1 query problem:

```php
// BAD: N+1 queries
$posts = Post::all();

foreach ($posts as $post) {
    echo $post->user->name; // Query for each post
}

// GOOD: 2 queries (posts + users)
$posts = Post::with('user')->get();

foreach ($posts as $post) {
    echo $post->user->name; // Already loaded
}
```

**Multiple relations:**

```php
$posts = Post::with(['user', 'comments'])->get();
```

**Nested eager loading:**

```php
$posts = Post::with(['user', 'comments.user'])->get();

foreach ($posts as $post) {
    echo $post->user->name;

    foreach ($post->comments as $comment) {
        echo $comment->user->name; // Already loaded
    }
}
```

**Auto eager loading:**

```php
class Post extends Facile
{
    public $with = ['user', 'category'];
}

// Automatically eager load user and category
$posts = Post::all();
```

<a id="constraining-eager-loading"></a>
## Constraining Eager Loading

```php
// Eager load with constraint
$users = User::with([
    'posts' => function ($query) {
        $query->where('published', '=', 1)
              ->order_by('created_at', 'desc');
    }
])->get();

// Multiple constraints
$users = User::with([
    'posts' => function ($query) {
        $query->where('votes', '>', 100);
    },
    'comments' => function ($query) {
        $query->where('approved', '=', 1);
    }
])->get();
```

<a id="inserting-related-models"></a>
## Inserting Related Models

**Has One / Has Many:**

```php
$user = User::find(1);

// Create and attach
$post = $user->posts()->create([
    'title' => 'New Post',
    'content' => 'Post content...',
]);

// Or with instance
$post = new Post;
$post->title = 'Another Post';
$post->content = 'Content...';

$user->posts()->save($post);
```

**Many To Many:**

```php
$user = User::find(1);
$role = Role::find(2);

// Attach role to user (insert into pivot table)
$user->roles()->attach($role->id);

// Attach multiple
$user->roles()->attach([1, 2, 3]);

// Detach
$user->roles()->detach($role->id);

// Detach all
$user->roles()->detach();

// Sync (replace all with new ones)
$user->roles()->sync([1, 2, 3]);
```

<a id="working-with-pivot-tables"></a>
## Working with Pivot Tables

**Pivot table with extra columns:**

```php
// role_user table
// Columns: user_id, role_id, expires_at, created_by
```

**Access pivot data:**

```php
$user = User::find(1);

foreach ($user->roles as $role) {
    echo $role->pivot->expires_at;
    echo $role->pivot->created_by;
}
```

**Insert with pivot data:**

```php
$user->roles()->attach($role_id, [
    'expires_at' => '2024-12-31',
    'created_by' => Auth::user()->id,
]);
```

<a id="converting-to-array--json"></a>
## Converting to Array & JSON

```php
$user = User::find(1);

// To array
$array = $user->to_array();

// To JSON
$json = $user->to_json();

// Or directly encode
echo json_encode($user);

// Array with relationships
$user = User::with('posts')->find(1);
$array = $user->to_array();

/*
[
    'id' => 1,
    'name' => 'John',
    'posts' => [
        ['id' => 1, 'title' => 'Post 1'],
        ['id' => 2, 'title' => 'Post 2'],
    ]
]
*/
```

<a id="global-scopes"></a>
## Global Scopes

Global scope is applied to all queries for the model:

```php
class User extends Facile
{
    protected static $global_scopes = [];

    public static function boot()
    {
        parent::boot();

        // Add global scope
        static::add_global_scope('active', function ($query) {
            $query->where('active', '=', 1);
        });
    }
}

// All queries automatically filtered active = 1
$users = User::all();
$user = User::find(1);

// Remove global scope
$all_users = User::without_global_scope('active')->get();
```

**Built-in soft delete scope:**

When `$soft_delete = true`, automatically has scope `WHERE deleted_at IS NULL`.

```php
$users = User::all(); // Only non-deleted

$all = User::with_trashed()->get(); // Including deleted

$deleted = User::only_trashed()->get(); // Only deleted
```
