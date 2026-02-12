# Error & Debugging

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Debug Bar](#debug-bar)
-   [Error & Exception](#error--exception)
-   [Configuration](#configuration)
-   [Logging](#logging)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Rakit comes with a fairly smart debugger feature, which can determine when it should display errors and exceptions.

It does this by guessing your application's environment based on the IP address. If you are on a local IP, the debugger will display the stack trace of errors occurring in your application.

Conversely, if you are not on a local IP, only the 500 (internal server error) page will be displayed. The error will be logged in the `storage/logs/` folder or sent to your email. You can also configure it as needed.

The 500 error page displayed comes from the file `application/views/error/500.php`. You can change its appearance if it doesn't suit you.

<a id="debug-bar"></a>

## Debug Bar

The Debug Bar is a floating panel displayed in the bottom right corner of your screen. This panel contains various information about your application such as page load time, server configuration, list of executed SQL queries, and the contents of variables you dump using the [bd()](/docs/id/helpers#bd) helper.



<a id="error--exception"></a>

## Error & Exception

Of course, you know how PHP reports errors:

```ini
Parse error:  syntax error, unexpected '}' in foo.php on line 22
```

```ini
Notice: Undefined variable: foo in /bar/baz/index.php on line 9
PHP Fatal error: Uncaught Error: Call to a member function lolcat() on null in /bar/baz/qux.php:9
Stack trace:
#0 {main}
thrown in /bar/baz/qux.php on line 9

Fatal error: Uncaught Error: Call to a member function lolcat() on null in /bar/baz/qux.php:9
Stack trace:
#0 {main}
thrown in /bar/baz/qux.php on line 9
```

It's certainly not easy to track output like this. With the debugger, errors and exceptions are displayed in a more human-friendly, readable, and detailed format:


This way, error messages are easier to track and handle. The error lines in your source code are also highlighted for easier handling. A clear message is also displayed in the header for you. Quite helpful, right?

Additionally, Fatal errors are also caught and displayed in the same way.

<a id="configuration"></a>

## Configuration

Of course, you are allowed to change the debugger configuration according to your needs. To do so, please edit the configuration in the file `application/config/debugger.php`.

<a id="logging"></a>

## Logging

Sometimes you might want to use the `Log` class for debugging, or just to log informational messages. Here's how to use it:

#### Writing messages to logs:

```php
Log::write('error', 'Failed to send email to Budi!');

Log::write('info', 'Email to Andi sent successfully!');
```

#### Using magic method to specify log type:

```php
Log::info('Email to Intan sent successfully!');
```

#### Including data in log messages:

```php
$user = User::find(1);

Log::write('info', 'User data: ', $user);
// or,
Log::info('User data: ', $user);
```
