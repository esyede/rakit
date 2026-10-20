# Hooks (Events & Listeners)

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Listening to an Event](#listening-to-an-event)
-   [Firing an Event](#firing-an-event)
-   [Queued Events](#queued-events)
-   [Framework Events](#framework-events)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Hooks are Rakit's event system: one piece of code fires a named event, and any number of listeners react to it without either side knowing about the other. The dispatcher is `System\Hook`, aliased as `Hook`. Application listeners usually live in `application/hooks.php`.

> The class is called `Hook` rather than `Event` because the PECL `event` extension defines a global `Event` class that would shadow the alias. `\System\Hook` always works.

<a id="listening-to-an-event"></a>

## Listening to an Event

Register a listener with `listen()`. Its arguments are the parameters the event is fired with:

```php
Hook::listen('loaded', function () {
    // Runs whenever 'loaded' is fired
});
```

Other helpers for managing listeners:

```php
Hook::exists('loaded');                     // Does the event have listeners?
Hook::override('loaded', function () { });  // Replace all listeners with this one
Hook::clear('loaded');                      // Remove all listeners
```

<a id="firing-an-event"></a>

## Firing an Event

`fire()` calls every listener and returns an array of their responses. Pass the listener arguments as the second parameter; the first may also be an array of event names:

```php
$responses = Hook::fire('loaded');
$responses = Hook::fire('user.created', [$user]);
```

`first()` returns only the first response (every listener still runs). `until()` stops at the first non-`NULL` response and returns it:

```php
$response = Hook::first('loaded');
$response = Hook::until('loaded');
```

The [dispatch()](/docs/helpers#dispatch) helper is a shorthand for `Hook::fire()`.

<a id="queued-events"></a>

## Queued Events

A queue collects payloads during the request so they can be processed together later. `queue()` takes a queue name, a key unique within that queue, and the payload:

```php
Hook::queue('foo', $user->id, [$user]);
```

Queuing the same key again replaces the earlier payload, so each item is processed once. Register a flusher, which receives the key followed by the payload items, then run it with `flush()`:

```php
Hook::flusher('foo', function ($key, $user) {
    // ...
});

Hook::flush('foo');
```

> `flush()` does not empty `Hook::$queued`. Unset the queue yourself if a second `flush()` must not replay the same payloads.

<a id="framework-events"></a>

## Framework Events

Rakit fires these events itself:

| Event                              | Fired with                | Listener arguments                                    |
| ---------------------------------- | ------------------------- | ----------------------------------------------------- |
| `rakit.done`                       | `fire`                    | `Response $response` — after the response is sent     |
| `rakit.schedule`                   | `fire`                    | none — when `schedule:run` / `schedule:list` starts; define [scheduled events](/docs/schedule) here |
| `rakit.terminated`                 | `fire`                    | `Response $response` — after the connection is finished (FastCGI) and the session sweep ran; for slow work the client should not wait on. Output is dropped |
| `rakit.reset`                      | `fire`                    | none — start of every worker request, after the framework reset its state |
| `rakit.log`                        | `fire`                    | `string $type, string $message, array $context`       |
| `rakit.query`                      | `fire`                    | `string $sql, array $bindings, string $time`          |
| `rakit.db.commit`                  | `fire`                    | `string $connection` — outermost transaction ended    |
| `rakit.db.rollback`                | `fire`                    | `string $connection` — outermost transaction ended    |
| `rakit.cache.hit`                  | `fire`                    | `string $key, float $time`                            |
| `rakit.cache.missed`               | `fire`                    | `string $key, float $time`                            |
| `rakit.resolving`                  | `fire`                    | `string $type, mixed $object`                         |
| `rakit.route.matched`              | `fire`                    | `Route $route, string $uri`                           |
| `rakit.locale`                     | `fire`                    | `string $locale` — URI started with a locale segment  |
| `rakit.composing: [view_name]`     | `fire`                    | `View $view`                                          |
| `rakit.booted: [package_name]`     | `fire`                    | none — that package booted                            |
| `rakit.booted`                     | `fire`                    | none — once per request, after `autoboot` packages    |
| `rakit.controller.factory`         | `first`                   | `string $class_name`                                  |
| `rakit.config.loader`              | `first`                   | `string $package, string $file`                       |
| `rakit.language.loader`            | `first`                   | `string $package, string $language, string $file`     |
| `rakit.view.loader`                | `until`                   | `string $package, string $view`                       |
| `rakit.view.engine`                | `until`                   | `View $view`                                          |
| `view.middleware`                  | `first`                   | `string $content, string $path`                       |
| `rakit.auth: login`                | `fire`                    | none                                                  |
| `rakit.auth: logout`               | `fire`                    | none                                                  |
| `rakit.auth: failed`               | `fire`                    | `array $credentials` (never contains `password`)      |
| `rakit.auth: lockout`              | `fire`                    | `string $key, int $limit, int $reset`                 |
| `facile.[event]`                   | `fire`                    | `Facile $model`                                       |
| `facile.[event]: [class_name]`     | `fire`                    | `Facile $model`                                       |
| `rakit.jobs.process`               | `fire`                    | `object\|array $job`                                  |
| `rakit.jobs.run: [job_name]`       | `fire`                    | `array $payloads`                                     |
| `rakit.jobs.forget: [job_name]`    | `fire`                    | none                                                  |
| `rakit.jobs.failed`                | `fire`                    | `string $name, array\|object $payloads, \Throwable $exception, int $attempts` |
| `rakit.mail.sending`               | `first`                   | `Message $message` — return `false` to cancel sending |
| `rakit.mail.sent`                  | `fire`                    | `Message $message`                                    |
| `404`                              | `first`                   | none                                                  |

For example:

```php
Hook::listen('rakit.query', function ($sql, $bindings, $time) {
    Log::info($sql.' ('.$time.'ms)');
});

Hook::listen('rakit.mail.sending', function ($message) {
    return false; // Cancels the sending
});
```

- **Model events:** `[event]` is one of `retrieved`, `saving`, `creating`, `created`, `updating`, `updated`, `saved`, `deleting`, `deleted`, `restoring`, `restored` or `replicating`. Each fires twice, with and without the class name, so you can watch one model or all of them. Returning `FALSE` from `saving`, `creating`, `updating`, `deleting` or `restoring` cancels the operation. See [Model Events](/docs/database/facile#model-events).
- **Job events:** drivers fire `rakit.jobs.process` with the raw job record; `System\Job` re-fires it as `rakit.jobs.run: [job_name]` with the unserialized payloads as the single argument, which is what your `Jobable` classes are bound to. `rakit.jobs.failed` fires when a job runs out of attempts.
- **`404` and `500`:** only `404` is fired (by the catch-all route and the controller resolver). There is no `500` event; uncaught exceptions are rendered through `Response::error(500)`.
