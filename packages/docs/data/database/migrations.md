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

Suppose you are working in a team, and each individual in your team has a local database for development.

A team member makes changes to the database, adding a new column. You pull the code with Git and try it locally,
then your application breaks because you don't have that new column yet. What would you do?

Migrations are the answer. Migrations can be used as version control for your database. Let's dig deeper to find out how to use them!

<a id="setting-up-the-database"></a>

## Setting Up the Database

Before running migrations, we need to do some work on your database.
Rakit uses a special table to record which migrations have been run.
To create that table, just run the following console command:

**Creating the migration record table:**

```bash
php rakit migrate:install
```

> Here we assume that you already have global access to PHP CLI.

<a id="creating-migration-files"></a>

## Creating Migration Files

You can easily create migrations through the [console](/docs/id/console) like this:

**Creating a migration file:**

```bash
php rakit make:migration create_users_table
```

Now, try opening the `application/migrations/` folder. You will see the new migration file you created there!
Note that the filename is prefixed with a timestamp. This allows Rakit to run your migrations in the correct order.

You can also create migration files for a package.

**Creating migration files for a package:**

```bash
php rakit make:migration nama_package::create_users_table
```

_Further reading:_

-   [Schema Builder](/docs/id/database/schema)

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

When you perform a roll back, all your migration operations will be reverted.
So, if the last migration command ran 122 migration operations, then those 122 operations will be reverted.

**Roll back to the last migration:**

```bash
php rakit migrate:rollback
```

**Reset all migrations:**

```bash
php rakit migrate:reset
```

**Reset and rerun all migrations:**

```bash
php rakit migrate:rebuild
```
