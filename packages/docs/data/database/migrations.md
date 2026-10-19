# Database Migrations

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Creating Migration Files](#creating-migration-files)
-   [Running Migrations](#running-migrations)
-   [Roll Back](#roll-back)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
<a id="setting-up-the-database"></a>

## Basic Knowledge

Migrations are version control for your database: each schema change lives in a file that every
teammate (and every server) can run, instead of only in someone's local database.

Rakit records the migrations it has run in a `rakit_migrations` table. `php rakit migrate` creates
it automatically, or you can create it yourself:

```bash
php rakit migrate:install
```

<a id="creating-migration-files"></a>

## Creating Migration Files

Create a migration with the [console](/docs/console):

```bash
php rakit make:migration create_users_table

# for a package
php rakit make:migration nama_package::create_users_table
```

The file lands in the `migrations/` folder of the application (or package), prefixed with a
timestamp so migrations run in order. It holds a class with an `up()` method that applies the
change and a `down()` method that reverses it, usually written with the
[Schema Builder](/docs/database/schema):

```php
class Create_Users_Table
{
    public function up()
    {
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email', 191)->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop_if_exists('users');
    }
}
```

<a id="running-migrations"></a>

## Running Migrations

```bash
php rakit migrate                # application and all packages
php rakit migrate application    # application only
php rakit migrate nama_package   # one package only
```

Each run groups its migrations into a new batch.

<a id="roll-back"></a>

## Roll Back

```bash
php rakit migrate:rollback   # revert the last batch
php rakit migrate:reset      # revert every migration
php rakit migrate:refresh    # revert every migration, then run them all again
```

A rollback reverts every migration in the last batch, however many it holds. Each of these
commands also accepts package names to limit it to those packages; the last batch is then
the last one *those packages* ran, even if other packages migrated after them:

```bash
php rakit migrate:rollback blog   # revert blog's last batch
php rakit migrate:reset blog      # revert every blog migration
php rakit migrate:refresh blog    # revert, then re-run, blog's migrations
```
