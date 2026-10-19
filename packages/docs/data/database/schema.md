# Schema Builder

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Creating & Deleting Tables](#creating--deleting-tables)
- [Listing Tables & Columns](#listing-tables--columns)
- [Column Types](#column-types)
  - [Numeric Types](#numeric-types)
  - [String Types](#string-types)
  - [Date & Time Types](#date--time-types)
  - [Binary Types](#binary-types)
  - [JSON Types](#json-types)
  - [Special Types](#special-types)
  - [Spatial Types](#spatial-types)
- [Column Modifiers](#column-modifiers)
- [Adding Columns](#adding-columns)
- [Changing Columns](#changing-columns)
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
- [Complete Example](#complete-example)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The Schema Builder creates, alters and drops tables with one database-agnostic API that works on
MySQL, PostgreSQL, SQLite and SQL Server. It is mostly used inside
[Migrations](/docs/database/migrations).

<a id="creating--deleting-tables"></a>
<a id="creating-tables"></a>
<a id="creating-tables-if-not-exists"></a>
<a id="deleting-tables"></a>
<a id="deleting-tables-if-exists"></a>
<a id="rename-tables"></a>
## Creating & Deleting Tables

`Schema::create()` takes the table name and a closure that receives a table blueprint:

```php
Schema::create('users', function ($table) {
    $table->increments('id');
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamps();
});

Schema::create_if_not_exists('users', function ($table) {
    // ..
});

Schema::rename('users', 'members');
Schema::drop('users');
Schema::drop_if_exists('users');
```

<a id="listing-tables--columns"></a>
<a id="listing-tables"></a>
<a id="listing-columns"></a>
<a id="check-table-exists"></a>
<a id="check-column-exists"></a>
## Listing Tables & Columns

```php
$tables  = Schema::tables();            // names of all tables
$columns = Schema::columns('users');    // names of all columns of a table

if (Schema::has_table('users')) { /* .. */ }
if (Schema::has_column('users', 'email')) { /* .. */ }
```

Each method accepts a connection name as its last argument, for example `Schema::tables('mysql')`.

<a id="column-types"></a>
## Column Types

<a id="numeric-types"></a>
### Numeric Types

| Method | Description |
|--------|-------------|
| `$table->increments('id')` | Auto-incrementing INTEGER (primary key) |
| `$table->integer('votes')` | INTEGER; `integer('id', true)` makes it auto-incrementing |
| `$table->biginteger('votes')` | BIGINT; `biginteger('id', true)` makes it auto-incrementing |
| `$table->mediuminteger('votes')` | MEDIUMINT equivalent |
| `$table->smallinteger('votes')` | SMALLINT equivalent |
| `$table->tinyinteger('votes')` | TINYINT equivalent |
| `$table->float('amount')` | FLOAT equivalent |
| `$table->double('amount')` | DOUBLE equivalent |
| `$table->decimal('amount', 8, 2)` | DECIMAL with precision and scale |
| `$table->boolean('confirmed')` | BOOLEAN equivalent (TINYINT(1) in MySQL) |

<a id="string-types"></a>
### String Types

| Method | Description |
|--------|-------------|
| `$table->string('name')` | VARCHAR with length 200 (default) |
| `$table->string('name', 100)` | VARCHAR with custom length |
| `$table->text('description')` | TEXT equivalent |
| `$table->longtext('content')` | LONGTEXT equivalent |

<a id="date--time-types"></a>
### Date & Time Types

| Method | Description |
|--------|-------------|
| `$table->date('published_at')` | DATETIME equivalent (date **and** time) |
| `$table->timestamp('created_at')` | TIMESTAMP equivalent |
| `$table->timestamps()` | Nullable `created_at` and `updated_at` TIMESTAMP columns |
| `$table->soft_deletes()` | Nullable `deleted_at` TIMESTAMP column |

<a id="binary-types"></a>
### Binary Types

| Method | Description |
|--------|-------------|
| `$table->blob('data')` | BLOB equivalent |

<a id="json-types"></a>
### JSON Types

| Method | Description |
|--------|-------------|
| `$table->json('options')` | JSON equivalent (TEXT in SQLite, NVARCHAR(MAX) in SQL Server) |
| `$table->jsonb('options')` | JSONB in PostgreSQL, same as `json()` on the other drivers |

<a id="special-types"></a>
### Special Types

| Method | Description |
|--------|-------------|
| `$table->enum('role', ['admin', 'user'])` | ENUM with allowed values |
| `$table->set('flags', ['a', 'b'])` | SET in MySQL; a string column with a CHECK constraint elsewhere |
| `$table->uuid('id')` | UUID equivalent (CHAR(36) in MySQL) |
| `$table->ipaddress('ip')` | IPv4/IPv6 address (VARCHAR(45) in MySQL, INET in PostgreSQL) |
| `$table->macaddress('mac')` | MAC address (VARCHAR(17) in MySQL, MACADDR in PostgreSQL) |

<a id="spatial-types"></a>
### Spatial Types

`geometry()`, `point()`, `linestring()`, `polygon()`, `geometrycollection()`, `multipoint()`,
`multilinestring()` and `multipolygon()` map to the native spatial types on MySQL, to PostGIS
`GEOMETRY(...)` on PostgreSQL, to `GEOMETRY` on SQL Server and to TEXT on SQLite:

```php
$table->point('location');
```

<a id="column-modifiers"></a>
## Column Modifiers

Chain modifiers after a column definition:

| Modifier | Description |
|----------|-------------|
| `->nullable()` | Allow NULL values |
| `->defaults($value)` | Set the default value |
| `->unsigned()` | Make an integer UNSIGNED |
| `->unique()` | Add a unique index (an optional argument names it) |
| `->index()` | Add a basic index (an optional argument names it) |
| `->primary()` | Make it the primary key |
| `->comment($text)` | Column comment (MySQL and PostgreSQL; ignored by SQLite, throws on SQL Server) |
| `->after($column)` | Place the new column after another one (MySQL only) |
| `->first()` | Place the new column first (MySQL only) |
| `->using($expression)` | `USING` expression that converts the old values when `change()` alters the type (PostgreSQL only) |
| `->change()` | Alter an existing column instead of adding it (see [Changing Columns](#changing-columns)) |

```php
$table->integer('votes')->unsigned()->defaults(0)->index();
$table->string('slug')->unique()->nullable();
$table->enum('role', ['admin', 'user'])->defaults('user');
```

<a id="adding-columns"></a>
## Adding Columns

`Schema::table()` alters an existing table. Columns defined in it are added:

```php
Schema::table('users', function ($table) {
    $table->string('phone')->nullable();
    $table->string('middle_name')->nullable()->after('first_name');
    $table->rename_column('bio', 'about');
});
```

<a id="changing-columns"></a>
## Changing Columns

Inside `Schema::table()`, end a column with `->change()` to alter the existing column instead of
adding a new one. Give the full new definition: modifiers that are left out are removed.

```php
Schema::table('users', function ($table) {
    $table->string('name', 100)->nullable()->change();
    $table->integer('votes')->defaults(0)->change();
});
```

| Driver | Generated SQL |
|--------|---------------|
| MySQL | `ALTER TABLE ... MODIFY col <full definition>` |
| PostgreSQL | `ALTER COLUMN col TYPE <type> USING col::<type>`, plus `SET/DROP NOT NULL` and `SET/DROP DEFAULT` |
| SQL Server | Drops the column's existing default constraint, then `ALTER COLUMN col <type> [NOT] NULL`; a default is added with `ADD DEFAULT ... FOR col` |
| SQLite | Not supported, throws an `Exception` |

On PostgreSQL the `USING col::<type>` cast converts the existing values. When a plain cast is not
enough, give your own expression with `->using()`:

```php
Schema::table('users', function ($table) {
    $table->integer('code')->using('trim("code")::integer')->change();
});
```

> Auto-increment columns keep their primary key, PostgreSQL sequence and SQL Server `IDENTITY`
> (which cannot be altered).

<a id="deleting-columns"></a>
## Deleting Columns

```php
Schema::table('users', function ($table) {
    $table->drop_column('phone');
    $table->drop_column(['bio', 'avatar']);
    $table->drop_column_if_exists('nickname');
});
```

> On SQLite, dropping a column needs SQLite 3.35.0+ and renaming one needs 3.25.0+; older
> versions throw. MySQL has no `IF EXISTS` for columns and indexes, so the `_if_exists` methods
> check `information_schema` first and skip the statement when the name is not there.

<a id="indexes"></a>
## Indexes

<a id="adding-index"></a>
### Adding Index

Each method takes a column or an array of columns (for a composite index), plus an optional
index name:

```php
Schema::table('users', function ($table) {
    $table->primary('id');
    $table->primary(['user_id', 'role_id']);

    $table->unique('email');
    $table->unique(['first_name', 'last_name'], 'users_full_name_unique');

    $table->index(['country', 'city']);
    $table->fulltext(['title', 'content']);
    $table->spatial_index('location');
});
```

Fulltext indexes differ per driver:

-   **MySQL** creates a regular `FULLTEXT` index.
-   **PostgreSQL** creates a GIN index on the `tsvector` of the columns, using the `english`
    config unless `->language()` gives another. Queries must repeat the same expression to use it:

    ```php
    $table->fulltext(['title', 'content'])->language('simple');

    // CREATE INDEX posts_title_content_fulltext ON "posts" USING gin((
    //     to_tsvector('simple', coalesce("title", '')) || to_tsvector('simple', coalesce("content", ''))
    // ))

    $posts = DB::table('posts')
        ->raw_where(
            "(to_tsvector('simple', coalesce(\"title\", '')) || to_tsvector('simple', coalesce(\"content\", ''))) @@ plainto_tsquery('simple', ?)",
            ['rakit framework']
        )
        ->get();
    ```

-   **SQLite** has no fulltext index on regular tables, so a separate FTS4 table named after the
    index (e.g. `posts_title_content_fulltext`) is created. It is not kept in sync; your application
    has to fill it.
-   **SQL Server** allows one fulltext index per table, stored in a catalog named after the index
    and keyed on the table's primary key. Give both explicitly for a table without a primary key:

    ```php
    $table->fulltext(['title', 'content'])->catalog('site_catalog')->key('posts_slug_unique');
    ```

    Dropping the index also drops its catalog unless another table still uses it. An explicit
    catalog must be repeated on the drop:
    `$table->drop_fulltext('posts_title_content_fulltext')->catalog('site_catalog');`

Spatial indexes use `SPATIAL INDEX` on MySQL and SQL Server and `gist` on PostgreSQL; SQLite does
not support them and throws.

<a id="deleting-index"></a>
### Deleting Index

Drop an index by name. Default names follow `{table}_{columns}_{type}`, for example
`users_email_unique` or `posts_title_content_fulltext`:

```php
Schema::table('users', function ($table) {
    $table->drop_primary();               // the name is optional
    $table->drop_unique('users_email_unique');
    $table->drop_index('users_country_index');
    $table->drop_fulltext('users_bio_fulltext');
});
```

`drop_unique_if_exists()`, `drop_index_if_exists()` and `drop_fulltext_if_exists()` skip indexes
that do not exist.

<a id="foreign-keys"></a>
## Foreign Keys

<a id="adding-foreign-key"></a>
### Adding Foreign Key

```php
Schema::create('posts', function ($table) {
    $table->increments('id');
    $table->integer('user_id')->unsigned();

    $table->foreign('user_id')
          ->references('id')
          ->on('users')
          ->on_delete('cascade')
          ->on_update('cascade');
});
```

`on_delete()` and `on_update()` accept `cascade`, `restrict`, `set null` and `no action`. Pass a
second argument to `foreign()` to name the constraint: `$table->foreign('user_id', 'fk_posts_user')`.

<a id="deleting-foreign-key"></a>
### Deleting Foreign Key

The default name is `{table}_{column}_foreign`:

```php
Schema::table('posts', function ($table) {
    $table->drop_foreign('posts_user_id_foreign');
    // or: $table->drop_foreign_if_exists('posts_user_id_foreign');
});
```

> SQLite cannot add or drop a foreign key or primary key on an existing table. Keys declared inside
> `Schema::create()` work, but `foreign()`, `drop_foreign()`, `primary()` and `drop_primary()` in
> `Schema::table()` throw. Recreate the table instead.

<a id="enabledisable-foreign-key-checks"></a>
### Enable/Disable Foreign Key Checks

Useful when seeding or truncating:

```php
Schema::disable_fk_checks('posts');
DB::table('posts')->delete();
Schema::enable_fk_checks('posts');
```

<a id="table-options"></a>
## Table Options

MySQL table options are set inside the closure:

```php
Schema::create('users', function ($table) {
    $table->engine = 'InnoDB';           // or $table->engine('InnoDB')
    $table->charset('utf8mb4');
    $table->collate('utf8mb4_unicode_ci');

    $table->increments('id');
});
```

<a id="database-connections"></a>
## Database Connections

Schema operations use the default connection. Pick another one inside the closure, or pass it as
the last argument of any `Schema` method:

```php
Schema::create('users', function ($table) {
    $table->connection('mysql');
    $table->increments('id');
});

Schema::table('users', function ($table) {
    $table->string('phone')->nullable();
}, 'mysql');

Schema::drop('users', 'mysql');
```

> Pass table names without the connection's `prefix`: `create()`, `drop()`, `has_table()`,
> `has_column()` and `columns()` add it themselves. `tables()` is the exception; it lists names as
> stored, prefix included.

<a id="complete-example"></a>
<a id="complete-examples"></a>
## Complete Example

```php
Schema::create('posts', function ($table) {
    $table->increments('id');
    $table->integer('user_id')->unsigned();
    $table->string('title', 255);
    $table->string('slug', 255)->unique();
    $table->longtext('content');
    $table->enum('status', ['draft', 'published'])->defaults('draft');
    $table->integer('views')->unsigned()->defaults(0);
    $table->timestamps();
    $table->soft_deletes();

    $table->foreign('user_id')->references('id')->on('users')->on_delete('cascade');
    $table->index(['status', 'created_at']);
});
```
