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
    - [make:component](#makecomponent)
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
    - [route:list](#routelist)
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

<a id="makecomponent"></a>
#### make:component

Creates a new blade component, both the class and the view it renders:

```bash
php rakit make:component ComponentName
```

Example:

```bash
php rakit make:component Alert
# Creates file: application/components/alert.php
# Creates file: application/views/components/alert.blade.php
```

The class is named after the file with `_Component` behind it, the same way a
controller is named `Home_Controller`, since there are no namespaces to keep the
names apart. The tag that reaches it is `<x-alert />`.

For a package, name it the way you would name one of its views:

```bash
php rakit make:component blog::alert
# Creates file: packages/blog/components/alert.php     (class Blog_Alert_Component)
# Creates file: packages/blog/views/components/alert.blade.php
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

Calls a route through the console (for testing). The request method and the URI
are passed as two separate arguments:

```bash
php rakit route:call get /
php rakit route:call post api/users
php rakit route:call get user/profile/123
```

<a id="routelist"></a>
#### route:list

Shows all registered routes along with their method, URI, action, and name:

```bash
php rakit route:list
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

You can create custom commands by creating files in `application/commands/`.
Extend the `Command` class to get the output and prompt helpers:

```php
<?php

class Syncdata_Command extends Command
{
    /**
     * Main method to be executed
     */
    public function run($arguments = [])
    {
        echo $this->info('Starting data synchronization...');

        // Your command logic here

        echo $this->info('Synchronization completed!');
    }

    /**
     * Sub-command (optional)
     */
    public function users($arguments = [])
    {
        echo $this->info('Synchronizing users...');

        // Special logic for syncing users
    }
}
```

> The helper methods return a string, they do not print anything by themselves.
> Do not forget the `echo`.

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

    echo $this->info('Processing: ' . $name);
}
```

#### Messages and Prompts

By extending `Command` you get these helpers:

| Method                        | Description                                          |
| ----------------------------- | ---------------------------------------------------- |
| `info($text, $newline)`       | Green message                                        |
| `warning($text, $newline)`    | Yellow message                                       |
| `error($text, $newline)`      | Red message                                          |
| `progress($percentage)`       | 10 block progress bar, `0` to `100`                  |
| `ask($question, $default)`    | Ask a question, returns the answer                   |
| `confirm($question, $default)`| Ask for `y` / `n`, returns a boolean                 |

```php
public function run($arguments = [])
{
    $name = $this->ask('Table name?', 'users');

    if (!$this->confirm('Truncate table ' . $name . '?')) {
        echo $this->warning('Cancelled.');
        return;
    }

    for ($i = 0; $i <= 100; $i += 10) {
        echo "\r" . $this->progress($i) . ' ' . $i . '%';
        usleep(50000);
    }

    echo PHP_EOL . $this->info('Done!');
}
```

#### Colored Output

Use `Color` if you need a color that the helpers above do not provide. The second
parameter decides whether a newline is appended (default `true`):

```php
use System\Console\Color;

echo Color::green('This is green text');
echo Color::red('This is red text');
echo Color::blue('This is blue text', false); // without newline
```

Available colors: `black()`, `red()`, `green()`, `yellow()`, `blue()`,
`purple()`, `cyan()`, and `white()`. The color is dropped automatically when the
terminal does not support it.

#### Creating Tables

`Table` is instantiated, filled row by row, then printed with `display()`:

```php
use System\Console\Table;

$table = new Table();

$table->set_headers(['Name', 'Email', 'Role']);
$table->add_row(['John Doe', 'john@example.com', 'Admin']);
$table->add_row(['Jane Smith', 'jane@example.com', 'User']);

$table->display();

/*
+------------+------------------+-------+
| Name       | Email            | Role  |
+------------+------------------+-------+
| John Doe   | john@example.com | Admin |
| Jane Smith | jane@example.com | User  |
+------------+------------------+-------+
*/
```

Other methods you can use:

| Method                              | Description                                    |
| ----------------------------------- | ---------------------------------------------- |
| `add_header($content)`              | Add one header column                          |
| `add_column($content, $col, $row)`  | Fill a single cell                             |
| `add_border_line()`                 | Insert a horizontal line between rows          |
| `show_borders()`                    | Draw a border on every row                     |
| `hide_border()` / `show_border()`   | Hide or show the outer border                  |
| `set_padding($value)`               | Cell padding (default `1`)                     |
| `set_indent($value)`                | Indent the whole table                         |
| `get_table()`                       | Return the table as a string instead of printing |

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
