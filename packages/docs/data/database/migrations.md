# Database Migrations

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Setting Up the Database](#setting-up-the-database)
-   [Creating Migration Files](#creating-migration-files)
-   [Running Migrations](#running-migrations)
-   [Roll Back](#roll-back)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Migrations are version control for your database. Without them, a column a teammate
added lives only in their local database, and your checkout breaks the moment you
pull their code.

<a id="setting-up-the-database"></a>

## Setting Up the Database

Rakit records the migrations it has run in a table of its own. Create it with:

**Creating the migration record table:**

```bash
php rakit migrate:install
```

> Here we assume that you already have global access to PHP CLI.

> This step is optional: `php rakit migrate` creates the `rakit_migrations` table automatically when it does not exist yet.

<a id="creating-migration-files"></a>

## Creating Migration Files

Create a migration through the [console](/docs/console):

**Creating a migration file:**

```bash
php rakit make:migration create_users_table
```

The file lands in `application/migrations/`, its name prefixed with a timestamp so
the migrations run in order.

You can also create migration files for a package.

**Creating migration files for a package:**

```bash
php rakit make:migration nama_package::create_users_table
```

_Further reading:_

-   [Schema Builder](/docs/database/schema)

<a id="running-migrations"></a>

## Running Migrations

**Running all migration files belonging to the application and packages:**

```bash
php rakit migrate
```

**Running all migration files belonging to the application:**

```bash
php rakit migrate application
```

**Running all migration files belonging to a package:**

```bash
php rakit migrate nama_package
```

<a id="roll-back"></a>

## Roll Back

A roll back reverts every operation of the last batch: 122 migrations run means 122
reverted.

**Roll back the last migration batch:**

```bash
php rakit migrate:rollback
```

**Reset all migrations:**

```bash
php rakit migrate:reset
```

**Reset and rerun all migrations:**

```bash
php rakit migrate:refresh
```
