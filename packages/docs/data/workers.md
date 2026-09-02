# Workers

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Supported Adapters](#supported-adapters)
- [How It Works](#how-it-works)
- [FrankenPHP](#frankenphp)
- [RoadRunner](#roadrunner)
- [Swoole](#swoole)
- [Custom Adapter](#custom-adapter)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Rakit ships with built-in worker bridges that let the framework run inside
long-lived worker processes such as FrankenPHP, RoadRunner, and Swoole.

Normally, PHP boots the framework from scratch on every request. In worker
mode the framework boots **once** and then serves thousands of requests from
the same process, which is significantly faster.

The bridge takes care of the hard part: resetting all per-request static state
between requests so that data from one user never leaks into the next.

<a id="supported-adapters"></a>

## Supported Adapters

| Adapter     | Class                          | Auto-detected via                    |
|-------------|--------------------------------|--------------------------------------|
| FrankenPHP  | `System\Bridges\Frankenphp`    | `function_exists('frankenphp_handle_request')` |
| RoadRunner  | `System\Bridges\Roadrunner`    | `getenv('RR_MODE')`                  |
| Swoole      | `System\Bridges\Swoole`        | `extension_loaded('swoole')`         |

<a id="how-it-works"></a>

## How It Works

When `index.php` boots, it checks whether the current environment is a
worker process. If it is, the framework defines `RAKIT_WORKER_MODE` and
`boot.php` returns early after initialization — skipping the normal
single-request dispatch.

Then `index.php` enters the worker loop:

```php
$runner = \System\Bridges\Worker::create($worker);
$runner->run();
```

On each incoming request the bridge:

1. **Resets** all static properties (`Container`, `Config` caches, `Hook`,
    `Session`, `Cookie`, `URI`, `Request`, `Package` boot guards, `Blade`,
    superglobals).
2. **Captures** the new request's globals (`$_SERVER`, `$_GET`, `$_POST`,
    `$_COOKIE`) and rebuilds `Request::$foundation`.
3. **Dispatches** the request through the normal Rakit pipeline (routing,
    controller, response).
4. **Sends** the response back through the adapter.

<a id="frankenphp"></a>

## FrankenPHP

FrankenPHP embeds PHP into the Caddy web server. Point the worker at
`index.php`:

```
# Caddyfile
frankenphp {
    worker index.php
}
```

That's it. Rakit auto-detects FrankenPHP and enters the worker loop.

<a id="roadrunner"></a>

## RoadRunner

RoadRunner is a Go-based application server. Install the RoadRunner PHP
client:

```bash
composer require spiral/roadrunner-http nyholm/psr7
```

Then configure `.rr.yaml`:

```yaml
server:
  command: "php index.php"

http:
  address: 0.0.0.0:8080
```

Rakit auto-detects RoadRunner via the `RR_MODE` environment variable that
RoadRunner sets automatically.

<a id="swoole"></a>

## Swoole

Swoole is a PHP extension for high-performance async servers. Because Swoole
uses an event-driven model (you create the server object yourself), the entry
point is slightly different:

```php
require __DIR__ . '/index.php';

$bridge = new \System\Bridges\Swoole();
$bridge->init();

$http = new \Swoole\Http\Server('0.0.0.0', 9501);

$http->on('request', function ($request, $response) use ($bridge) {
    $bridge->handle_request($request, $response);
});

$http->start();
```

<a id="custom-adapter"></a>

## Custom Adapter

To add a new adapter, extend `System\Bridges\Bridge` and implement
`wait_request()`:

```php
namespace System\Bridges;

class Fooserver extends Bridge
{
    public function init()
    {
        // One-time setup (exit guards, signals, etc.)
    }

    public function wait_request()
    {
        // Block until the next request arrives.
        // Return true to continue, false to stop the loop.
    }

    public function send_response($response)
    {
        // Override if your server uses a different response API.
        parent::send_response($response);
    }
}
```

Then register it in `Worker::create()` by adding your adapter name to the
`$map` array inside `system/bridges/worker.php`.
