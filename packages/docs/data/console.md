# Console

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Running Commands](#running-commands)
- [Available Commands](#available-commands)
  - [Clear Commands](#clear-commands)
  - [Fiddle Commands](#fiddle-commands)
    - [fiddle](#fiddle)
  - [Job Commands](#job-commands)
  - [Maintenance Commands](#maintenance-commands)
  - [Make Commands](#make-commands)
    - [make:component](#makecomponent)
    - [make:auth](#makeauth)
  - [Migration Commands](#migration-commands)
  - [Package Commands](#package-commands)
  - [Routing Commands](#routing-commands)
  - [Schedule Commands](#schedule-commands)
  - [Session Commands](#session-commands)
  - [Test Commands](#test-commands)
  - [Webserver Commands](#webserver-commands)
  - [Websocket Commands](#websocket-commands)
- [Global Options](#global-options)
- [Creating Custom Commands](#creating-custom-commands)
  - [Command Structure](#command-structure)
  - [Command Registration](#command-registration)
  - [Input & Output](#input--output)
- [Running Commands From Code](#running-commands-from-code)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The console is Rakit's command-line tool for everyday development tasks: generating controllers, models and migrations, running migrations and jobs, clearing caches, and more. It is the `rakit` file in the project root.

<a id="running-commands"></a>

## Running Commands

Run commands from the project root:

```bash
php rakit command:name [arguments] [--options]
```

Run `php rakit` or `php rakit help` to list all commands.

<a id="available-commands"></a>

## Available Commands

<a id="clear-commands"></a>

### Clear Commands

<a id="clearcache"></a>
<a id="clearviews"></a>
<a id="clearlogs"></a>

| Command       | Description                                       |
| ------------- | ------------------------------------------------- |
| `clear:cache` | Clear everything stored by the default cache driver |
| `clear:views` | Clear the compiled Blade views in `storage/views` |
| `clear:logs`  | Delete the log files in `storage/logs`            |

<a id="fiddle-commands"></a>

### Fiddle Commands

<a id="fiddle"></a>

#### fiddle

Starts Fiddle, an interactive PHP console (REPL) with the framework loaded:

```bash
php rakit fiddle
```

```text
[1] FIDDLE> echo 1 + 1;
2
[2] FIDDLE>
```

- Multi-line statements (a `function`, `class` or `if` block) continue on a `*>` prompt until complete.
- `Tab` completes class names, models, helper functions and `Class::method`. The arrow keys walk through the history, kept in `storage/console/.fiddle_history`.
- Every executed query is printed above the result as `-- SQL (ms)`.
- `help()` lists the built-in shortcuts (`models()`, `db()`, `table()`, `dump()`). `help`, `exit` and `quit` also work without a semicolon.
- An error is reported and the console stays open, keeping your variables.
- `Ctrl+C` clears an unfinished statement; `exit;`, `quit;` or `Ctrl+D` leaves.
- With PCNTL and POSIX available it runs each statement in a forked process (`fork` mode). Elsewhere (e.g. Windows) it falls back to `inline` mode in a single process, where a fatal error ends the session and `Ctrl+C` cannot abort a running statement.

<a id="job-commands"></a>

### Job Commands

<a id="jobrun"></a>
<a id="jobrunall"></a>

| Command                    | Description                                                  |
| -------------------------- | ------------------------------------------------------------ |
| `job:run name [name ...]`  | Run the due jobs with the given name(s)                      |
| `job:runall`               | Run the due jobs of every queue; accepts `--queue`, `--retries`, `--sleep` |
| `job:work`                 | Keep running jobs until stopped; also `--idle`, `--max-jobs`, `--max-time`, `--memory` |
| `job:failed`               | List the failed jobs                                         |
| `job:retry id\|all`        | Put failed jobs back on their queue                          |

See [Running Worker](/docs/job#running-worker) for the options.

<a id="maintenance-commands"></a>

### Maintenance Commands

| Command | Description |
| --- | --- |
| `down` | Answer every request with a 503 (the `error.503` view, or JSON) |
| `up` | Bring the application back |

```bash
php rakit down --secret=let-me-in --retry=60 --allow=203.0.113.7
php rakit up
```

- `--secret` lets your team in: visiting `/let-me-in` sets a cookie that bypasses maintenance for
  12 hours, then redirects home. Only a hash of the secret is stored, and running `down` again
  with another secret invalidates the cookies.
- `--retry` sends a `Retry-After` header, in seconds, telling clients and crawlers when to come back.
- `--allow` lets the given client IPs through.

Maintenance mode is a file, `storage/down.php`, so it covers every server that shares `storage/`;
on servers with their own, run `down` on each. Commands, jobs and the scheduler keep running.
The application also ships a `/up` route in `application/routes.php` for load balancers and
uptime monitors: it answers `200 OK`, and `503` during maintenance.

<a id="make-commands"></a>

### Make Commands

Generate files from templates. Prefix the name with a package (`blog::post`) to create the file inside that package.

<a id="makecontroller"></a>
<a id="makeresource"></a>
<a id="makemodel"></a>
<a id="makemigration"></a>
<a id="makecommand"></a>
<a id="makejob"></a>
<a id="makeobserver"></a>
<a id="maketransformer"></a>
<a id="maketest"></a>

| Command                              | Creates                                                         |
| ------------------------------------ | --------------------------------------------------------------- |
| `make:controller user`               | `application/controllers/user.php`                              |
| `make:resource post`                 | `application/controllers/post.php` with `get_index`, `get_create`, `post_store`, `get_show`, `get_edit`, `put_update`, `delete_destroy` |
| `make:model user`                    | `application/models/user.php`                                   |
| `make:migration create_users_table`  | `application/migrations/<timestamp>_create_users_table.php`     |
| `make:command syncdata`              | `application/commands/syncdata.php` (`Syncdata_Command`)        |
| `make:job notify`                    | `application/jobs/notify.php` (`Notify_Job`)                    |
| `make:observer user`                 | `application/observers/user.php` (`User_Observer`)              |
| `make:transformer user`              | `application/transformers/user.php` (`User_Transformer`)        |
| `make:test user`                     | `application/tests/user.test.php` (`UserTest`)                  |
| `make:component alert`               | A component class and its view, see below                       |
| `make:auth`                          | Authentication scaffolding, see below                           |

In a package, class names get the package prefix: `make:observer blog::post` creates `packages/blog/observers/post.php` with class `Blog_Post_Observer`.

<a id="makecomponent"></a>

#### make:component

Creates a Blade component class and the view it renders:

```bash
php rakit make:component alert
# application/components/alert.php            (class Alert_Component)
# application/views/components/alert.blade.php

php rakit make:component blog::alert
# packages/blog/components/alert.php          (class Blog_Alert_Component)
# packages/blog/views/components/alert.blade.php
```

The class name gets a `_Component` suffix (like `Home_Controller`), and the tag that renders it is `<x-alert />`.

<a id="makeauth"></a>

#### make:auth

Generates authentication scaffolding: login, registration and password-reset screens with their controllers, a layout, a dashboard, and the routes:

```bash
php rakit make:auth
# application/views/auth/login.blade.php
# application/views/auth/register.blade.php
# application/views/auth/passwords/email.blade.php
# application/views/auth/passwords/reset.blade.php
# application/views/auth/email/reset.blade.php
# application/views/layouts/app.blade.php
# application/views/dashboard.blade.php
# application/controllers/auth/login.php
# application/controllers/auth/register.php
# application/controllers/auth/password.php
# application/controllers/dashboard.php
```

These routes are appended to `application/routes.php`:

```php
Route::get('login', 'auth.login@show');
Route::post('login', ['as' => 'login', 'uses' => 'auth.login@login']);
Route::post('logout', 'auth.login@logout');

Route::get('register', 'auth.register@show');
Route::post('register', ['as' => 'register', 'uses' => 'auth.register@register']);

Route::get('password/email', 'auth.password@show_resend');
Route::post('password/email', 'auth.password@resend');
Route::get('password/reset/(:any?)', 'auth.password@show_reset');
Route::post('password/reset', 'auth.password@reset');

Route::get('/dashboard', 'dashboard@index');
```

> Running the command again appends the routes again. Delete the `Auto-generated auth routes` block from `application/routes.php` first.

<a id="migration-commands"></a>

### Migration Commands

Each command accepts an optional package name (`php rakit migrate blog`) to limit it to that package.

<a id="migrate"></a>
<a id="migraterollback"></a>
<a id="migratereset"></a>
<a id="migraterefresh"></a>

| Command            | Description                                       |
| ------------------ | ------------------------------------------------- |
| `migrate`          | Run all pending migrations                        |
| `migrate:rollback` | Roll back the last batch                          |
| `migrate:reset`    | Roll back all migrations                          |
| `migrate:refresh`  | Roll back all migrations, then run them again     |
| `migrate:install`  | Create the `rakit_migrations` tracking table      |

<a id="package-commands"></a>

### Package Commands

<a id="packageinstall"></a>
<a id="packageuninstall"></a>
<a id="packageupgrade"></a>
<a id="packagepublish"></a>
<a id="packageunpublish"></a>

| Command                    | Description                                         |
| -------------------------- | --------------------------------------------------- |
| `package:install name`     | Install a package from the repository (add `--verbose=true` for detailed output) |
| `package:uninstall name`   | Uninstall a package                                 |
| `package:upgrade name`     | Upgrade a package to the latest version             |
| `package:publish name`     | Publish the package's assets to the public folder   |
| `package:unpublish name`   | Remove the published assets                         |

<a id="routing-commands"></a>

### Routing Commands

<a id="routecall"></a>
<a id="routelist"></a>

| Command                   | Description                                                  |
| ------------------------- | ------------------------------------------------------------ |
| `route:call method uri`   | Call a route from the console, e.g. `route:call get user/profile/123`. Middleware is skipped unless `--with-middleware` is given |
| `route:list`              | List all routes with their method, URI, action and name      |

<a id="schedule-commands"></a>

### Schedule Commands

| Command | Description |
| --- | --- |
| `schedule:run` | Run the scheduled events that are due; call it from cron every minute |
| `schedule:list` | List the scheduled events |

See [Task Scheduling](/docs/schedule).

<a id="session-commands"></a>

### Session Commands

<a id="sessiongc"></a>

`session:gc` removes expired sessions. Only the `file` and `database` drivers need it; other drivers expire their own data.

<a id="test-commands"></a>

### Test Commands

<a id="testrun"></a>
<a id="testcore"></a>
<a id="testpackage"></a>

| Command                  | Description                                            |
| ------------------------ | ------------------------------------------------------ |
| `test:run`               | Run the application tests; arguments are treated as package names, like `test:package` |
| `test:core`              | Run the framework's core tests                         |
| `test:package name`      | Run a package's tests                                  |

To send requests through the application from a test, see [HTTP Tests](/docs/testing).

<a id="webserver-commands"></a>

### Webserver Commands

<a id="serve"></a>

`serve` starts a development web server at `http://localhost:8000`. Change it with `--host` and `--port`:

```bash
php rakit serve --host=0.0.0.0 --port=8080
```

<a id="websocket-commands"></a>

### Websocket Commands

<a id="websocketrun"></a>

`websocket:run` starts the [WebSocket](/docs/websocket) server on `127.0.0.1:6001`, also configurable with `--host` and `--port`:

```bash
php rakit websocket:run --host=0.0.0.0 --port=9000
```

<a id="global-options"></a>

## Global Options

| Option            | Description                                     | Example                                   |
| ----------------- | ----------------------------------------------- | ----------------------------------------- |
| `--env=`          | Run under another environment                   | `php rakit migrate --env=testing`         |
| `--database=`     | Use another default database connection         | `php rakit migrate --database=pgsql`      |
| `--verbose=true`  | Detailed output (`package:install`)             | `php rakit package:install foo --verbose=true` |
| `--host=`         | Host for `serve` and `websocket:run`            | `php rakit serve --host=127.0.0.1`        |
| `--port=`         | Port for `serve` and `websocket:run`            | `php rakit serve --port=3000`             |
| `--with-middleware` | Run route middleware (`route:call`)           | `php rakit route:call get admin --with-middleware` |

<a id="creating-custom-commands"></a>

## Creating Custom Commands

<a id="command-structure"></a>

### Command Structure

Put commands in `application/commands/` (or generate one with `make:command`) and extend `Command`. `run()` is the default method; any other public method is a sub-command. Each receives the command-line arguments as an array:

```php
// application/commands/syncdata.php

class Syncdata_Command extends Command
{
    public function run($arguments = [])
    {
        $name = isset($arguments[0]) ? $arguments[0] : 'all';

        echo $this->info('Synchronizing '.$name.'...');
    }

    public function users($arguments = [])
    {
        echo $this->info('Synchronizing users...');
    }
}
```

<a id="command-registration"></a>

### Command Registration

Commands are found by convention, without registration: the file name is lowercase (`syncdata.php`) and the class is its classified name plus `_Command` (`Syncdata_Command`).

```bash
php rakit syncdata            # run()
php rakit syncdata:users      # users()
php rakit blog::syncdata      # a command in the "blog" package
```

<a id="input--output"></a>

### Input & Output

`Command` provides these helpers. They **return** a string rather than printing it, so don't forget the `echo`:

| Method                         | Description                                 |
| ------------------------------ | ------------------------------------------- |
| `info($text, $newline)`        | Green message                               |
| `warning($text, $newline)`     | Yellow message                              |
| `error($text, $newline)`       | Red message                                 |
| `progress($percentage)`        | 10-block progress bar, `0` to `100`         |
| `ask($question, $default)`     | Ask a question, return the answer           |
| `confirm($question, $default)` | Ask for `y` / `n`, return a boolean         |

```php
public function run($arguments = [])
{
    $name = $this->ask('Table name?', 'users');

    if (! $this->confirm('Truncate table '.$name.'?')) {
        echo $this->warning('Cancelled.');
        return;
    }

    for ($i = 0; $i <= 100; $i += 10) {
        echo "\r".$this->progress($i).' '.$i.'%';
        usleep(50000);
    }

    echo PHP_EOL.$this->info('Done!');
}
```

For other colors, use `System\Console\Color`: `black()`, `red()`, `green()`, `yellow()`, `blue()`, `purple()`, `cyan()` and `white()`. The second parameter appends a newline (default `true`), and colors are dropped when the terminal doesn't support them:

```php
use System\Console\Color;

echo Color::blue('This is blue text', false);
```

`System\Console\Table` prints tables:

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

| Method                              | Description                                      |
| ----------------------------------- | ------------------------------------------------ |
| `add_header($content)`              | Add one header column                            |
| `add_column($content, $col, $row)`  | Fill a single cell                               |
| `add_border_line()`                 | Insert a horizontal line between rows            |
| `show_borders()`                    | Draw a border on every row                       |
| `hide_border()` / `show_border()`   | Hide or show the outer border                    |
| `set_padding($value)`               | Cell padding (default `1`)                       |
| `set_indent($value)`                | Indent the whole table                           |
| `get_table()`                       | Return the table as a string instead of printing |

<a id="running-commands-from-code"></a>

## Running Commands From Code

`Console::run()` takes the command name, or an array of the name followed by its arguments:

```php
use System\Console\Console;

Console::run('clear:cache');
Console::run(['migrate:rollback', 'application']);
```

For example, from a route:

```php
Route::get('admin/clear-cache', function () {
    Console::run('clear:cache');

    return Redirect::back()->with('message', 'Cache cleared!');
});
```
