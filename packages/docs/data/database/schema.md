# Schema Builder

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Creating & Deleting Tables](#creating--deleting-tables)
  - [Creating Tables](#creating-tables)
  - [Creating Tables If Not Exists](#creating-tables-if-not-exists)
  - [Deleting Tables](#deleting-tables)
  - [Deleting Tables If Exists](#deleting-tables-if-exists)
  - [Rename Tables](#rename-tables)
- [Listing Tables & Columns](#listing-tables--columns)
  - [Listing Tables](#listing-tables)
  - [Listing Columns](#listing-columns)
  - [Check Table Exists](#check-table-exists)
  - [Check Column Exists](#check-column-exists)
- [Column Types](#column-types)
  - [Numeric Types](#numeric-types)
  - [String Types](#string-types)
  - [Date & Time Types](#date--time-types)
  - [Binary Types](#binary-types)
  - [JSON Types](#json-types)
  - [Special Types](#special-types)
- [Column Modifiers](#column-modifiers)
- [Adding Columns](#adding-columns)
- [Deleting Columns](#deleting-columns)
- [Indexes](#indexes)
  - [Adding Index](#adding-index)
  - [Deleting Index](#deleting-index)
- [Foreign Keys](#foreign-keys)
  - [Adding Foreign Key](#adding-foreign-key)
  - [Deleting Foreign Key](#deleting-foreign-key)
  - [Enable/Disable Foreign Key Checks](#enabledisable-foreign-key-checks)
- [Table Options](#table-options)
- [Database Connections](#database-connections)
- [Complete Examples](#complete-examples)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

Schema Builder provides a database-agnostic API for creating and modifying tables. Schema Builder works with all databases supported by Rakit and provides a unified API for all database systems.

**Advantages of using Schema Builder:**

- Consistent syntax for all databases
- No need to write different raw SQL
- Safer and avoids typos
- Integrated with the Migration system

<a id="creating--deleting-tables"></a>
## Creating & Deleting Tables

<a id="creating-tables"></a>
### Creating Tables

Use the `Schema::create()` method to create a new table:

```php
Schema::create('users', function ($table) {
    $table->increments('id');
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamps();
});
```

<a id="creating-tables-if-not-exists"></a>
### Creating Tables If Not Exists

To avoid errors if the table already exists:

```php
Schema::create_if_not_exists('users', function ($table) {
    $table->increments('id');
    $table->string('name');
    $table->string('email');
});
```

<a id="deleting-tables"></a>
### Deleting Tables

```php
Schema::drop('users');
```

<a id="deleting-tables-if-exists"></a>
### Deleting Tables If Exists

```php
Schema::drop_if_exists('users');
```

<a id="rename-tables"></a>
### Rename Tables

```php
Schema::rename('users', 'members');
```

<a id="listing-tables--columns"></a>
## Listing Tables & Columns

<a id="listing-tables"></a>
### Listing Tables

Get a list of all tables in the database:

```php
// Default connection
$tables = Schema::tables();

// Specific connection
$tables = Schema::tables('mysql');

foreach ($tables as $table) {
    echo $table . "\n";
}
```

<a id="listing-columns"></a>
### Listing Columns

Get a list of all columns in a table:

```php
// Default connection
$columns = Schema::columns('users');

// Specific connection
$columns = Schema::columns('users', 'mysql');

foreach ($columns as $column) {
    echo $column . "\n";
}
```

<a id="check-table-exists"></a>
### Check Table Exists

```php
if (Schema::has_table('users')) {
    echo 'Table users already exists';
}

// With specific connection
if (Schema::has_table('users', 'mysql')) {
    echo 'Table users exists in mysql connection';
}
```

<a id="check-column-exists"></a>
### Check Column Exists

```php
if (Schema::has_column('users', 'email')) {
    echo 'Column email exists in table users';
}

// With specific connection
if (Schema::has_column('users', 'email', 'mysql')) {
    echo 'Column email exists';
}
```

<a id="column-types"></a>
## Column Types

<a id="numeric-types"></a>
### Numeric Types

| Method | Description |
|--------|-------------|
| `$table->increments('id')` | Auto-incrementing UNSIGNED INTEGER (primary key) |
| `$table->biginteger('votes')` | BIGINT equivalent |
| `$table->integer('votes')` | INTEGER equivalent |
| `$table->mediuminteger('votes')` | MEDIUMINT equivalent |
| `$table->smallinteger('votes')` | SMALLINT equivalent |
| `$table->tinyinteger('votes')` | TINYINT equivalent |
| `$table->float('amount')` | FLOAT equivalent |
| `$table->double('amount')` | DOUBLE equivalent |
| `$table->decimal('amount', 8, 2)` | DECIMAL with precision and scale |
| `$table->boolean('confirmed')` | BOOLEAN equivalent (TINYINT in MySQL) |

**Example:**

```php
Schema::create('products', function ($table) {
    $table->increments('id');
    $table->biginteger('views')->unsigned();
    $table->integer('stock');
    $table->decimal('price', 10, 2);
    $table->float('rating');
    $table->boolean('is_active')->defaults(true);
});
```

<a id="string-types"></a>
### String Types

| Method | Description |
|--------|-------------|
| `$table->string('name')` | VARCHAR with length 200 (default) |
| `$table->string('name', 100)` | VARCHAR with custom length |
| `$table->text('description')` | TEXT equivalent |
| `$table->longtext('content')` | LONGTEXT equivalent |

**Example:**

```php
Schema::create('posts', function ($table) {
    $table->increments('id');
    $table->string('title', 255);
    $table->string('slug', 255)->unique();
    $table->text('excerpt')->nullable();
    $table->longtext('content');
});
```

<a id="date--time-types"></a>
### Date & Time Types

| Method | Description |
|--------|-------------|
| `$table->date('created_at')` | DATE equivalent |
| `$table->timestamp('created_at')` | TIMESTAMP equivalent |
| `$table->timestamps()` | Adds `created_at` and `updated_at` TIMESTAMP |

**Example:**

```php
Schema::create('events', function ($table) {
    $table->increments('id');
    $table->string('name');
    $table->date('event_date');
    $table->timestamp('starts_at')->nullable();
    $table->timestamp('ends_at')->nullable();
    $table->timestamps();
});
```

<a id="binary-types"></a>
### Binary Types

| Method | Description |
|--------|-------------|
| `$table->blob('data')` | BLOB equivalent |

**Example:**

```php
Schema::create('files', function ($table) {
    $table->increments('id');
    $table->string('filename');
    $table->blob('data');
});
```

<a id="json-types"></a>
### JSON Types

| Method | Description |
|--------|-------------|
| `$table->json('options')` | JSON equivalent (TEXT in MySQL < 5.7) |
| `$table->jsonb('options')` | JSONB equivalent (PostgreSQL only) |

**Example:**

```php
Schema::create('settings', function ($table) {
    $table->increments('id');
    $table->string('key')->unique();
    $table->json('value');
});
```

<a id="special-types"></a>
### Special Types

| Method | Description |
|--------|-------------|
| `$table->enum('role', ['admin', 'user'])` | ENUM with allowed values |
| `$table->uuid('id')` | UUID equivalent (CHAR(36)) |
| `$table->ipaddress('ip')` | IP Address (VARCHAR(45) for IPv4/IPv6) |

**Example:**

```php
Schema::create('users', function ($table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->enum('role', ['admin', 'moderator', 'user'])->defaults('user');
    $table->ipaddress('last_login_ip')->nullable();
});
```

<a id="column-modifiers"></a>
## Column Modifiers

Column modifiers can be chained after column definition:

| Modifier | Description |
|----------|-------------|
| `->nullable()` | Allow NULL values |
| `->defaults($value)` | Set default value |
| `->unsigned()` | Set INTEGER to UNSIGNED |
| `->unique()` | Add unique index |
| `->primary()` | Set as primary key |
| `->index()` | Add basic index |

**Example:**

```php
Schema::create('users', function ($table) {
    $table->increments('id');
    $table->string('email')->unique();
    $table->string('name')->nullable();
    $table->integer('age')->unsigned()->nullable();
    $table->boolean('is_active')->defaults(1);
    $table->string('status')->defaults('pending')->index();
    $table->timestamps();
});
```

**Chaining multiple modifiers:**

```php
$table->integer('votes')->unsigned()->defaults(0)->index();
$table->string('slug')->unique()->nullable();
```

<a id="adding-columns"></a>
## Adding Columns

To add columns to an existing table, use `Schema::table()`:

```php
Schema::table('users', function ($table) {
    $table->string('phone')->nullable();
    $table->text('bio')->nullable();
});
```

**Adding columns with index:**

```php
Schema::table('users', function ($table) {
    $table->string('username')->unique();
    $table->integer('votes')->defaults(0)->index();
});
```

**Adding columns after a specific column:**

```php
Schema::table('users', function ($table) {
    $table->string('middle_name')->nullable()->after('first_name');
});
```

<a id="deleting-columns"></a>
## Deleting Columns

**Deleting a single column:**

```php
Schema::table('users', function ($table) {
    $table->drop_column('phone');
});
```

**Deleting multiple columns:**

```php
Schema::table('users', function ($table) {
    $table->drop_column(['phone', 'bio', 'avatar']);
});
```

<a id="indexes"></a>
## Indexes

<a id="adding-index"></a>
### Adding Index

**Primary Key:**

```php
Schema::table('users', function ($table) {
    $table->primary('id');

    // Composite primary key
    $table->primary(['user_id', 'role_id']);
});
```

**Unique Index:**

```php
Schema::table('users', function ($table) {
    $table->unique('email');

    // Composite unique
    $table->unique(['first_name', 'last_name']);

    // With custom name
    $table->unique('email', 'users_email_unique_idx');
});
```

**Basic Index:**

```php
Schema::table('users', function ($table) {
    $table->index('country');

    // Composite index
    $table->index(['country', 'city']);

    // With custom name
    $table->index('email', 'idx_users_email');
});
```

**Full-Text Index:**

```php
Schema::table('posts', function ($table) {
    $table->fulltext('title');

    // Multiple columns
    $table->fulltext(['title', 'content']);
});
```

**Index when creating columns:**

```php
Schema::create('users', function ($table) {
    $table->increments('id');
    $table->string('email')->unique();
    $table->string('username')->index();
    $table->string('country')->index();
});
```

<a id="deleting-index"></a>
### Deleting Index

**Naming convention for indexes:**

Format: `{table}_{column}_{type}`

Examples:
- Primary: `users_id_primary`
- Unique: `users_email_unique`
- Index: `posts_title_index`
- Fulltext: `posts_content_fulltext`

**Drop primary key:**

```php
Schema::table('users', function ($table) {
    $table->drop_primary('users_id_primary');
});
```

**Drop unique index:**

```php
Schema::table('users', function ($table) {
    $table->drop_unique('users_email_unique');
});
```

**Drop basic index:**

```php
Schema::table('users', function ($table) {
    $table->drop_index('users_country_index');
});
```

**Drop fulltext index:**

```php
Schema::table('posts', function ($table) {
    $table->drop_fulltext('posts_title_fulltext');
});
```

<a id="foreign-keys"></a>
## Foreign Keys

<a id="adding-foreign-key"></a>
### Adding Foreign Key

**Basic foreign key:**

```php
Schema::create('posts', function ($table) {
    $table->increments('id');
    $table->integer('user_id')->unsigned();
    $table->string('title');

    // Foreign key
    $table->foreign('user_id')
          ->references('id')
          ->on('users');
});
```

**With ON DELETE and ON UPDATE:**

```php
Schema::table('posts', function ($table) {
    $table->foreign('user_id')
          ->references('id')
          ->on('users')
          ->on_delete('cascade')
          ->on_update('cascade');
});
```

**ON DELETE options:**
- `cascade` - Delete child records when parent is deleted
- `restrict` - Prevent deletion of parent if children exist
- `set null` - Set foreign key to NULL when parent is deleted
- `no action` - Same as restrict

**ON UPDATE options:**
- `cascade` - Update child records when parent is updated
- `restrict` - Prevent update of parent if children exist
- `set null` - Set foreign key to NULL when parent is updated
- `no action` - Same as restrict

**Complete example:**

```php
Schema::create('comments', function ($table) {
    $table->increments('id');
    $table->integer('post_id')->unsigned();
    $table->integer('user_id')->unsigned();
    $table->text('content');
    $table->timestamps();

    // Foreign keys
    $table->foreign('post_id')
          ->references('id')
          ->on('posts')
          ->on_delete('cascade');

    $table->foreign('user_id')
          ->references('id')
          ->on('users')
          ->on_delete('cascade');
});
```

**Custom foreign key name:**

```php
$table->foreign('user_id', 'fk_comments_user')
      ->references('id')
      ->on('users');
```

<a id="deleting-foreign-key"></a>
### Deleting Foreign Key

**Naming convention:**

Format: `{table}_{column}_foreign`

Example: `posts_user_id_foreign`

```php
Schema::table('posts', function ($table) {
    $table->drop_foreign('posts_user_id_foreign');
});
```

<a id="enabledisable-foreign-key-checks"></a>
### Enable/Disable Foreign Key Checks

Useful when seeding or truncating tables:

```php
// Disable
Schema::disable_fk_checks('posts');

// Truncate or other operations
DB::table('posts')->delete();

// Enable back
Schema::enable_fk_checks('posts');
```

<a id="table-options"></a>
## Table Options

**Set storage engine (MySQL):**

```php
Schema::create('users', function ($table) {
    $table->engine = 'InnoDB';

    $table->increments('id');
    $table->string('name');
});
```

**Set charset (MySQL):**

```php
Schema::create('users', function ($table) {
    $table->charset('utf8mb4');

    $table->increments('id');
    $table->string('name');
});
```

**Set collation (MySQL):**

```php
Schema::create('users', function ($table) {
    $table->collate('utf8mb4_unicode_ci');

    $table->increments('id');
    $table->string('name');
});
```

**Combination:**

```php
Schema::create('users', function ($table) {
    $table->engine = 'InnoDB';
    $table->charset('utf8mb4');
    $table->collate('utf8mb4_unicode_ci');

    $table->increments('id');
    $table->string('name');
    $table->timestamps();
});
```

<a id="database-connections"></a>
## Database Connections

**Specify the connection to use:**

```php
Schema::create('users', function ($table) {
    $table->on('mysql');

    $table->increments('id');
    $table->string('name');
});
```

**Or in drop method:**

```php
Schema::drop('users', 'mysql');
```

**Listing with specific connection:**

```php
$tables = Schema::tables('pgsql');
$columns = Schema::columns('users', 'sqlite');
```

<a id="complete-examples"></a>
## Complete Examples

**Creating users table with relations:**

```php
Schema::create('users', function ($table) {
    $table->engine = 'InnoDB';
    $table->charset('utf8mb4');

    $table->increments('id');
    $table->string('name', 100);
    $table->string('email', 255)->unique();
    $table->string('password', 255);
    $table->string('phone', 20)->nullable()->index();
    $table->enum('role', ['admin', 'user'])->defaults('user');
    $table->boolean('is_active')->defaults(1);
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamps();
    $table->timestamp('deleted_at')->nullable();

    $table->index(['role', 'is_active']);
});
```

**Creating table with foreign key:**

```php
Schema::create('posts', function ($table) {
    $table->engine = 'InnoDB';

    $table->increments('id');
    $table->integer('user_id')->unsigned();
    $table->integer('category_id')->unsigned()->nullable();
    $table->string('title', 255);
    $table->string('slug', 255)->unique();
    $table->text('excerpt')->nullable();
    $table->longtext('content');
    $table->enum('status', ['draft', 'published', 'archived'])->defaults('draft');
    $table->integer('views')->unsigned()->defaults(0);
    $table->timestamps();

    $table->foreign('user_id')
          ->references('id')
          ->on('users')
          ->on_delete('cascade');

    $table->foreign('category_id')
          ->references('id')
          ->on('categories')
          ->on_delete('set null');

    $table->index('status');
    $table->index(['status', 'created_at']);
    $table->fulltext(['title', 'content']);
});
```

**Pivot table for many-to-many:**

```php
Schema::create('role_user', function ($table) {
    $table->engine = 'InnoDB';

    $table->increments('id');
    $table->integer('user_id')->unsigned();
    $table->integer('role_id')->unsigned();
    $table->timestamps();

    $table->foreign('user_id')
          ->references('id')
          ->on('users')
          ->on_delete('cascade');

    $table->foreign('role_id')
          ->references('id')
          ->on('roles')
          ->on_delete('cascade');

    $table->unique(['user_id', 'role_id']);
});
```
