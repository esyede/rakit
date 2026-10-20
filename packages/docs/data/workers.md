# Workers

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Supported Adapters](#supported-adapters)
- [FrankenPHP](#frankenphp)
- [RoadRunner](#roadrunner)
- [Swoole](#swoole)
- [How It Works](#how-it-works)
- [Limitations](#limitations)
- [Custom Adapter](#custom-adapter)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Normally PHP boots the framework from scratch on every request. In worker mode the framework boots **once** and the same process then serves many requests, which is much faster. Rakit ships bridges for FrankenPHP, RoadRunner and Swoole that reset all per-request state between requests, so one user's data never leaks into the next.

<a id="supported-adapters"></a>

## Supported Adapters

| Adapter     | Class                       | Detected via                                      |
|-------------|-----------------------------|---------------------------------------------------|
| FrankenPHP  | `System\Worker\Frankenphp`  | `$_SERVER['FRANKENPHP_WORKER']`                   |
| RoadRunner  | `System\Worker\Roadrunner`  | `getenv('RR_MODE') === 'http'`                    |
| Swoole      | `System\Worker\Swoole`      | `RAKIT_WORKER_MODE` defined by your server script |

<a id="frankenphp"></a>

## FrankenPHP

FrankenPHP embeds PHP in the Caddy web server. Register `index.php` as the worker script; Rakit detects the worker and enters its loop:

```caddyfile
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

<a id="roadrunner"></a>

## RoadRunner

Install RoadRunner's PHP HTTP client, then point `.rr.yaml` at `index.php`. RoadRunner sets `RR_MODE=http` for its HTTP workers, which is how Rakit detects it.

```bash
composer require spiral/roadrunner-http
```

```yaml
version: "3"

server:
  command: "php index.php"

http:
  address: 0.0.0.0:8080
```

> RoadRunner talks to its workers over STDOUT. Output you `echo` is captured and prepended to the response body, but never write to STDOUT directly.

<a id="swoole"></a>

## Swoole

Swoole leaves no trace to detect, and you create the server object yourself, so it needs its own entry script. Define `RAKIT_WORKER_MODE` before requiring `index.php` so the framework only boots:

```php
// server.php
define('RAKIT_WORKER_MODE', 'swoole');

require __DIR__.'/index.php';

$bridge = new \System\Worker\Swoole();
$bridge->init();

$http = new \Swoole\Http\Server('0.0.0.0', 9501);
$http->set(['enable_coroutine' => false]);

$http->on('request', function ($request, $response) use ($bridge) {
    $bridge->handle_request($request, $response);
});

$http->start();
```

Run it with `php server.php`.

> Always set `enable_coroutine` to `false`. With coroutines on, Swoole can interleave requests in one process, and the framework's static state (current request, session, authenticated user) leaks between users.

<a id="how-it-works"></a>

## How It Works

When `index.php` detects a worker it defines `RAKIT_WORKER_MODE`, `boot.php` returns right after initialization, and `index.php` enters the loop (Swoole runs its own event loop instead):

```php
\System\Worker\Worker::create(RAKIT_WORKER_MODE)->run();
```

For each request the worker:

1. **Resets** per-request static state: `Request`, `URI`, `URL`, `Input`, `Session`, `Cookie`, `Auth`, route groups, sections, views, the query log and queued hooks. Container singletons, the application language and the environment are restored to their post-boot values. Then `rakit.reset` fires, for packages that keep their own statics.
2. **Captures** the request from `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES` and the body, and rebuilds `Request::$foundation`.
3. **Dispatches** it through the normal pipeline (session, routing, middlewares, controller, session save).
4. **Sends** the response through the adapter and fires `rakit.done`.
5. **Terminates**: runs the deferred session sweep and fires `rakit.terminated`. A failure there is logged, never sent.

Exceptions never reach the server: they become a 500 response (the debugger page in debug mode, or the `error.500` view plus a log entry in production), so a failing request never kills the worker.

<a id="limitations"></a>

## Limitations

- **`exit`, `die` and `dd()` stop the whole worker**, not just the request, and the framework must boot again in a new one. (`dd()` only does this in debug mode.) Return a response instead.
- **`abort()`, `abort_if()` and `first_or_fail()` are safe**: in a worker they throw `System\Exceptions\HttpResponseException`, which the worker turns into the error response (the session is still saved). Don't swallow it with a bare `catch (\Exception $e)` around such calls; rethrow it, or catch a narrower class.
- **The debug bar is not rendered** in worker mode. The debugger's exception page still works.
- **Your own static state** (static properties, `Config::set()` calls) survives into the next request. Reset it yourself, e.g. in a `rakit.done` listener.
- **RoadRunner uploads** are not registered with PHP, so `is_uploaded_file()`, the `file` validation rule and `Upload::move()` reject them. Read them from `Input::file()` and copy the `tmp_name`.
- **`Response::download()`** returns a response instead of streaming under RoadRunner and Swoole, so return it from your route.

<a id="custom-adapter"></a>

## Custom Adapter

Extend `System\Worker\Bridge` and implement `wait_request()`:

```php
namespace System\Worker;

class Fooserver extends Bridge
{
    public function init()
    {
        parent::init();
        // One-time setup (connect to the server, register signals, ...)
    }

    public function wait_request(\Closure $handler)
    {
        // Block until the next request arrives and fill the superglobals
        // (or override capture() to fill them from a request object).
        // Call $handler() to serve it; return true to continue, false to stop.
    }

    public function send_response(\System\Response $response, $level)
    {
        // Override if your server has its own response API.
        // Output buffers above $level belong to the request.
        parent::send_response($response, $level);
    }
}
```

Then add your adapter name to the `$map` array in `Worker::create()` (`system/worker/worker.php`).
