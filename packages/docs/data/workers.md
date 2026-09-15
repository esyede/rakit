# Workers

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Supported Adapters](#supported-adapters)
- [How It Works](#how-it-works)
- [FrankenPHP](#frankenphp)
- [RoadRunner](#roadrunner)
- [Swoole](#swoole)
- [Limitations](#limitations)
- [Custom Adapter](#custom-adapter)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Rakit ships with built-in worker Worker that let the framework run inside
long-lived worker processes such as FrankenPHP, RoadRunner, and Swoole.

Normally, PHP boots the framework from scratch on every request. In worker
mode the framework boots **once** and then serves thousands of requests from
the same process, which is significantly faster.

The bridge takes care of the hard part: resetting all per-request static state
between requests so that data from one user never leaks into the next.

<a id="supported-adapters"></a>

## Supported Adapters

| Adapter     | Class                          | Detected via                                   |
|-------------|--------------------------------|------------------------------------------------|
| FrankenPHP  | `System\Worker\Frankenphp`    | `$_SERVER['FRANKENPHP_WORKER']`                |
| RoadRunner  | `System\Worker\Roadrunner`    | `getenv('RR_MODE') === 'http'`                 |
| Swoole      | `System\Worker\Swoole`        | `RAKIT_WORKER_MODE` defined by your server script |

<a id="how-it-works"></a>

## How It Works

When `index.php` boots, it checks whether the current process is a worker.
If it is, the framework defines `RAKIT_WORKER_MODE` and `boot.php` returns
early after initialization — skipping the normal single-request dispatch.

Then `index.php` enters the worker loop (Swoole runs its own event loop instead):

```php
\System\Worker\Worker::create(RAKIT_WORKER_MODE)->run();
```

On each incoming request the worker:

1. **Resets** per-request static state (`Request`, `URI`, `URL`, `Input`,
    `Session`, `Cookie`, `Auth`, route groups, sections, views, the query log and
    queued hooks). Container singletons are restored to what existed right
    after boot, and so are the application language and environment.
2. **Captures** the new request from `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE`,
    `$_FILES` and the request body, and rebuilds `Request::$foundation`.
3. **Dispatches** the request through the normal Rakit pipeline: loading the
    session, routing, middlewares, the controller, and saving the session.
4. **Sends** the response back through the adapter and fires `rakit.done`.

Exceptions never reach the server. They are turned into a 500 response: the
debugger's error page in debug mode, or the `error.500` view (plus a log entry)
in production, so a failing request never takes the worker down with it.

<a id="frankenphp"></a>

## FrankenPHP

FrankenPHP embeds PHP into the Caddy web server. Register `index.php` as the
worker script and serve the application folder:

```
# Caddyfile
{
    frankenphp {
        worker ./index.php
    }
}

localhost {
    root * .
    php_server
}
```

That's it. Rakit detects the worker and enters the loop.

<a id="roadrunner"></a>

## RoadRunner

RoadRunner is a Go-based application server. Install its PHP HTTP client:

```bash
composer require spiral/roadrunner-http
```

Then configure `.rr.yaml`:

```yaml
version: "3"

server:
  command: "php index.php"

http:
  address: 0.0.0.0:8080
```

RoadRunner sets `RR_MODE=http` for its HTTP workers, which is how Rakit detects it.

> RoadRunner talks to its workers over STDOUT. Output your code echoes is caught
> and put in front of the response body, but never write to STDOUT directly.

<a id="swoole"></a>

## Swoole

Swoole is a PHP extension for high-performance async servers. Because Swoole
uses an event-driven model (you create the server object yourself), the entry
point is a script of its own. Define `RAKIT_WORKER_MODE` before requiring
`index.php`, so the framework only boots instead of handling a request:

```php
define('RAKIT_WORKER_MODE', 'swoole');

require __DIR__.'/index.php';

$bridge = new \System\Worker\Swoole();
$bridge->init();

$http = new \Swoole\Http\Server('0.0.0.0', 9501);

// Requests share the framework's static state, so they must not interleave.
$http->set(['enable_coroutine' => false]);

$http->on('request', function ($request, $response) use ($bridge) {
    $bridge->handle_request($request, $response);
});

$http->start();
```

Then run it with `php server.php`.

> **Warning:** Always set `enable_coroutine` to `false`, as the example above
> does. With coroutines enabled, Swoole can pause one request and start another
> in the same process, so requests get mixed up and static state (the current
> request, session, authenticated user) leaks from one user to another.

<a id="limitations"></a>

## Limitations

A worker keeps running between requests, so a few things behave differently:

- **`exit`, `die` and `dd()` stop the whole worker**, not only the current
    request. The framework has to boot again in a new worker, which throws away
    the speed gain. `dd()` only does this in debug mode. Return a response from
    your route instead of ending the script.
- **The debug bar is not rendered** in worker mode, even when debug mode is on.
    The debugger's error page for exceptions still works.
- Static state your own code keeps (static properties, `Config::set()` calls)
    survives into the next request. Reset it yourself, for example in a
    `rakit.done` listener.
- Under RoadRunner, uploaded files are not registered with PHP, so
    `is_uploaded_file()`, the `file` validation rule and `Upload::move()` reject them.
    Read them from `Input::file()` and copy the `tmp_name` instead.
- `Response::download()` returns a response instead of streaming the file under
    RoadRunner and Swoole, so return it from your route.

<a id="custom-adapter"></a>

## Custom Adapter

To add a new adapter, extend `System\Worker\Bridge` and implement
`wait_request()`:

```php
namespace System\Worker;

class Fooserver extends Bridge
{
    public function init()
    {
        parent::init();

        // One-time setup (connect to the server, register signals, etc.)
    }

    public function wait_request(\Closure $handler)
    {
        // Block until the next request arrives and fill the PHP superglobals
        // (override capture() if they have to be filled from a request object).
        // Call $handler() to serve it, return true to continue, or false to stop.
    }

    public function send_response(\System\Response $response, $level)
    {
        // Override if your server uses a different response API. Output buffers
        // above $level belong to the request.
        parent::send_response($response, $level);
    }
}
```

Then register it in `Worker::create()` by adding your adapter name to the
`$map` array inside `system/Worker/worker.php`.
