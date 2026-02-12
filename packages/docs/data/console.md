# Console

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Running Commands](#running-commands)
- [Available Commands](#available-commands)
  - [Clear Commands](#clear-commands)
    - [clear:cache](#clearcache)
    - [clear:views](#clearviews)
    - [clear:logs](#clearlogs)
  - [Job Commands](#job-commands)
    - [job:run](#jobrun)
    - [job:runall](#jobrunall)
  - [Make Commands](#make-commands)
    - [make:controller](#makecontroller)
    - [make:resource](#makeresource)
    - [make:model](#makemodel)
    - [make:migration](#makemigration)
    - [make:command](#makecommand)
    - [make:job](#makejob)
    - [make:test](#maketest)
  - [Migration Commands](#migration-commands)
    - [migrate](#migrate)
    - [migrate:rollback](#migraterollback)
    - [migrate:reset](#migratereset)
    - [migrate:refresh](#migraterefresh)
  - [Package Commands](#package-commands)
    - [package:install](#packageinstall)
    - [package:uninstall](#packageuninstall)
    - [package:upgrade](#packageupgrade)
    - [package:publish](#packagepublish)
    - [package:unpublish](#packageunpublish)
  - [Routing Commands](#routing-commands)
    - [route:call](#routecall)
  - [Session Commands](#session-commands)
    - [session:gc](#sessiongc)
  - [Test Commands](#test-commands)
    - [test:run](#testrun)
    - [test:core](#testcore)
    - [test:package](#testpackage)
  - [Webserver Commands](#webserver-commands)
    - [serve](#serve)
  - [Websocket Commands](#websocket-commands)
    - [websocket:run](#websocketrun)
- [Global Options](#global-options)
- [Creating Custom Commands](#creating-custom-commands)
  - [Command Structure](#command-structure)
  - [Command Registration](#command-registration)
  - [Input & Output](#input--output)
- [Running Commands From Code](#running-commands-from-code)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

Rakit Console is a CLI tool that provides various commands to help with application development. These commands can help you create controllers, models, migrations, run migrations, clear cache, and more.

The console can be accessed through the `rakit` file in your project's root directory.

<a id="running-commands"></a>
## Running Commands

To run a command, open the terminal and navigate to the project root directory, then run:

```bash
php rakit command:name [arguments] [options]
```

To see a list of all available commands:

```bash
php rakit help
# or
php rakit
```

To see help for a specific command:

```bash
php rakit help:command command:name
```

<a id="available-commands"></a>
## Available Commands

<a id="clear-commands"></a>
### Clear Commands

<a id="clearcache"></a>
#### clear:cache

Clears all cached data stored in storage/cache:

```bash
php rakit clear:cache
```

<a id="clearviews"></a>
#### clear:views

Clears all compiled views (Blade templates) in storage/views:

```bash
php rakit clear:views
```

<a id="clearlogs"></a>
#### clear:logs

Clears all log files in storage/logs:

```bash
php rakit clear:logs
```

<a id="job-commands"></a>
### Job Commands

<a id="jobrun"></a>
#### job:run

Runs a specific job:

```bash
php rakit job:run job_name
```

Example:

```bash
php rakit job:run send_email
```

<a id="jobrunall"></a>
#### job:runall

Runs all registered jobs:

```bash
php rakit job:runall
```

<a id="make-commands"></a>
### Make Commands

Commands to create new files with templates.

<a id="makecontroller"></a>
#### make:controller

Creates a new controller:

```bash
php rakit make:controller ControllerName
```

Example:

```bash
php rakit make:controller User
# Creates file: application/controllers/user.php
```

To create a controller in a package:

```bash
php rakit make:controller package::ControllerName
```

<a id="makeresource"></a>
#### make:resource

Creates a RESTful resource controller:

```bash
php rakit make:resource ResourceName
```

This controller will have methods: index, create, store, show, edit, update, destroy.

Example:

```bash
php rakit make:resource Post
# Creates file: application/controllers/post.php with RESTful methods
```

<a id="makemodel"></a>
#### make:model

Creates a new model:

```bash
php rakit make:model ModelName
```

Example:

```bash
php rakit make:model User
# Creates file: application/models/user.php
```

<a id="makemigration"></a>
#### make:migration

Creates a new migration file:

```bash
php rakit make:migration migration_name
```

Example:

```bash
php rakit make:migration create_users_table
# Creates file: application/migrations/2024_01_15_123456_create_users_table.php
```

For package migrations:

```bash
php rakit make:migration package::migration_name
```

<a id="makecommand"></a>
#### make:command

Creates a new custom command:

```bash
php rakit make:command CommandName
```

Example:

```bash
php rakit make:command SyncData
# Creates file: application/commands/syncdata.php
```

<a id="makejob"></a>
#### make:job

Creates a new job for background processing:

```bash
php rakit make:job JobName
```

Example:

```bash
php rakit make:job SendWelcomeEmail
# Creates file: application/jobs/sendwelcomeemail.php
```

<a id="maketest"></a>
#### make:test

Creates a new unit test:

```bash
php rakit make:test TestName
```

Example:

```bash
php rakit make:test UserTest
# Creates file: application/tests/usertest.php
```

<a id="migration-commands"></a>
### Migration Commands

<a id="migrate"></a>
#### migrate

Runs all pending migrations:

```bash
php rakit migrate
```

For a specific package:

```bash
php rakit migrate package_name
```

<a id="migraterollback"></a>
#### migrate:rollback

Rolls back the last migration:

```bash
php rakit migrate:rollback
```

For a specific package:

```bash
php rakit migrate:rollback package_name
```

<a id="migratereset"></a>
#### migrate:reset

Rolls back all migrations:

```bash
php rakit migrate:reset
```

For a specific package:

```bash
php rakit migrate:reset package_name
```

<a id="migraterefresh"></a>
#### migrate:refresh

Rolls back all migrations then runs them again:

```bash
php rakit migrate:refresh
```

For a specific package:

```bash
php rakit migrate:refresh package_name
```

<a id="package-commands"></a>
### Package Commands

<a id="packageinstall"></a>
#### package:install

Installs a package from repository:

```bash
php rakit package:install package_name
```

With verbose output:

```bash
php rakit package:install package_name --verbose=true
```

<a id="packageuninstall"></a>
#### package:uninstall

Uninstalls a package:

```bash
php rakit package:uninstall package_name
```

<a id="packageupgrade"></a>
#### package:upgrade

Upgrades a package to the latest version:

```bash
php rakit package:upgrade package_name
```

<a id="packagepublish"></a>
#### package:publish

Publishes package assets to the public folder:

```bash
php rakit package:publish package_name
```

<a id="packageunpublish"></a>
#### package:unpublish

Removes published package assets:

```bash
php rakit package:unpublish package_name
```

<a id="routing-commands"></a>
### Routing Commands

<a id="routecall"></a>
#### route:call

Calls a route through the console (for testing):

```bash
php rakit route:call "GET /route/path"
```

Example:

```bash
php rakit route:call "GET /"
php rakit route:call "POST /api/users"
php rakit route:call "GET /user/profile/123"
```

<a id="session-commands"></a>
### Session Commands

<a id="sessiongc"></a>
#### session:gc

Clears expired sessions from the database:

```bash
php rakit session:gc
```

<a id="test-commands"></a>
### Test Commands

<a id="testrun"></a>
#### test:run

Runs tests in the application folder:

```bash
php rakit test:run
```

Runs a specific test:

```bash
php rakit test:run TestName
```

<a id="testcore"></a>
#### test:core

Runs core framework tests:

```bash
php rakit test:core
```

<a id="testpackage"></a>
#### test:package

Runs tests for a specific package:

```bash
php rakit test:package package_name
```

<a id="webserver-commands"></a>
### Webserver Commands

<a id="serve"></a>
#### serve

Runs a development web server:

```bash
php rakit serve
```

With custom host and port:

```bash
php rakit serve --host=0.0.0.0 --port=8080
```

The server will run at `http://localhost:8000` by default.

<a id="websocket-commands"></a>
### Websocket Commands

<a id="websocketrun"></a>
#### websocket:run

Runs a websocket server:

```bash
php rakit websocket:run
```

With custom host and port:

```bash
php rakit websocket:run --host=0.0.0.0 --port=9000
```

<a id="global-options"></a>
## Global Options

Some options that can be used with all commands:

### --env

Changes the environment:

```bash
php rakit migrate --env=production
php rakit migrate --env=testing
```

### --database

Changes the default database connection:

```bash
php rakit migrate --database=mysql
php rakit migrate --database=pgsql
```

### --verbose

Displays more detailed output:

```bash
php rakit package:install my_package --verbose=true
```

### --host

Changes the host (for serve and websocket):

```bash
php rakit serve --host=127.0.0.1
```

### --port

Changes the port (for serve and websocket):

```bash
php rakit serve --port=3000
```

<a id="creating-custom-commands"></a>
## Creating Custom Commands

<a id="command-structure"></a>
### Command Structure

You can create custom commands by creating files in `application/commands/`:

```php
<?php

class Syncdata_Command
{
    /**
     * Main method to be executed
     */
    public function run($arguments = [])
    {
        Console\Table::write('info', 'Starting data synchronization...');

        // Your command logic here

        Console\Table::write('success', 'Synchronization completed!');
    }

    /**
     * Sub-command (optional)
     */
    public function users($arguments = [])
    {
        Console\Table::write('info', 'Synchronizing users...');

        // Special logic for syncing users
    }
}
```

<a id="command-registration"></a>
### Command Registration

Commands are automatically registered if they follow the naming convention:
- Filename: lowercase with underscores (example: `syncdata.php`)
- Classname: PascalCase with `_Command` suffix (example: `Syncdata_Command`)

Running the command:

```bash
# Runs the run() method
php rakit syncdata

# Runs the users() method
php rakit syncdata:users
```

<a id="input--output"></a>
### Input & Output

#### Accessing Arguments

```php
public function run($arguments = [])
{
    // $arguments[0] is the first argument after command name
    $name = isset($arguments[0]) ? $arguments[0] : 'default';

    Console\Table::write('info', 'Processing: ' . $name);
}
```

#### Colored Output

```php
use System\Console\Table;
use System\Console\Color;

// Success message (green)
Table::write('success', 'Operation completed!');

// Error message (red)
Table::write('error', 'Something went wrong!');

// Info message (blue)
Table::write('info', 'Processing...');

// Warning message (yellow)
Table::write('warning', 'Be careful!');

// Custom colored text
echo Color::green('This is green text');
echo Color::red('This is red text');
echo Color::blue('This is blue text');
echo Color::yellow('This is yellow text');
```

#### Creating Tables

```php
use System\Console\Table;

$data = [
    ['Name', 'Email', 'Role'],
    ['John Doe', 'john@example.com', 'Admin'],
    ['Jane Smith', 'jane@example.com', 'User'],
];

Table::show($data);

/*
Output:
+------------+-------------------+-------+
| Name       | Email             | Role  |
+------------+-------------------+-------+
| John Doe   | john@example.com  | Admin |
| Jane Smith | jane@example.com  | User  |
+------------+-------------------+-------+
*/
```

#### Progress Indicator

```php
Console\Table::write('info', 'Processing items...');

$total = 100;
for ($i = 1; $i <= $total; $i++) {
    // Process item

    // Show progress
    echo "\rProgress: " . $i . '/' . $total;
    usleep(50000); // 50ms delay
}

echo "\n";
Console\Table::write('success', 'Done!');
```

<a id="running-commands-from-code"></a>
## Running Commands From Code

You can run commands from your application:

```php
use System\Console\Console;

// Run a single command
Console::run('migrate');

// With arguments
Console::run(['migrate:rollback', 'application']);

// Run clear cache
Console::run('clear:cache');
```

Example in a route:

```php
Route::get('admin/clear-cache', function () {
    Console::run('clear:cache');
    return Redirect::back()->with('message', 'Cache cleared!');
});
```

Or in a job:

```php
class Cleanup_Job
{
    public function run()
    {
        Console::run('clear:cache');
        Console::run('clear:logs');
        Console::run('session:gc');
    }
}
```
