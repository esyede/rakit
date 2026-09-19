# Hooks (Events & Listeners)

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Firing an Event](#firing-an-event)
-   [Listening to an Event](#listening-to-an-event)
-   [Queued Events](#queued-events)
-   [Framework Events](#framework-events)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Hooks are Rakit's event-listener system. They let your code emit named
events and other code subscribe to those events without either side knowing
about the other — useful for decoupling classes, libraries, and packages.

The dispatcher class is `System\Hook` and is registered as the short alias
`Hook`. Application-level listeners typically live in
`application/hooks.php`.

> The class is named `Hook` (not `Event`) because PHP's `event` PECL
> extension exposes a built-in `Event` class that would shadow the alias.
> If you prefer the fully-qualified form, use `\System\Hook::listen(...)` —
> it works regardless of alias configuration.

<a id="firing-an-event"></a>

## Firing an Event

Fire an event by name:

#### Firing an event:

```php
$responses = Hook::fire('loaded');
```

`fire()` returns an array holding the response of every listener. For the first one only:

#### Firing an event and getting only the first response:

```php
$response = Hook::first('loaded');
```

> `first()` still runs every listener, it only returns the first response.

`Hook::until()` runs the listeners one by one and stops at the first response that is not `NULL`, returning it.

#### Firing an event and getting the first non-NULL response:

```php
$response = Hook::until('loaded');
```

> The `dispatch()` helper is a shorthand for `Hook::fire()` — see
> [dispatch()](/docs/helpers#dispatch).

<a id="listening-to-an-event"></a>

## Listening to an Event

An event is only useful with listeners. Register one with `listen()`:

#### Registering a listener to the event named `'loaded'`:

```php
Hook::listen('loaded', function () {
    // I will be called when the 'loaded' event is fired
});
```

<a id="queued-events"></a>

## Queued Events

`queue()` and `flush()` hold events back to be run later. Queue an item under a queue name and a key of its own:

#### Queuing an event:

```php
Hook::queue('foo', $user->id, [$user]);
```

The three parameters are the queue name, a key unique within that queue, and the payload for the flusher.

> The key is an array key, so queuing it twice replaces the earlier payload. The
> queue de-duplicates by itself, which is handy for collecting items during a
> request and processing each one once at the end.

Register a flusher for that queue:

#### Registering an event flusher:

```php
Hook::flusher('foo', function ($key, $user) {
    // ...
});
```

The flusher receives the key first, here the user ID, then the payload items.
`flush()` runs it over everything queued:

```php
Hook::flush('foo');
```

> `flush()` does not empty `Hook::$queued` afterwards. Unset the queue yourself if you need to prevent a second `flush()` from replaying the same payloads.

<a id="framework-events"></a>

## Framework Events

Events Rakit fires itself:

#### Event fired when a package is booted:

```php
Hook::listen('rakit.booted: package', function () { });
```

#### Event fired when a database query is executed:

```php
Hook::listen('rakit.query', function ($sql, $bindings, $time) { });
```

#### Event fired right after a response has been sent to the browser:

```php
Hook::listen('rakit.done', function ($response) { });
```

#### Event fired when a message is logged using the `Log` class:

```php
Hook::listen('rakit.log', function ($type, $message) { });
```

The complete list, with the parameters each one carries:

| Command                                         | Parameter                                             |
| ----------------------------------------------- | ----------------------------------------------------- |
| `Hook::fire('rakit.done',`                     | `[Response $response]);`                              |
| `Hook::fire('rakit.log',`                      | `[string $type, string $message, array $context]);`   |
| `Hook::fire('rakit.query',`                    | `[string $sql, array $bindings, string $time]);`      |
| `Hook::fire('rakit.resolving',`                | `[string $type, mixed $object]);`                     |
| `Hook::fire('rakit.composing: [view_name]',`   | `[View $view]);`                                      |
| `Hook::fire('rakit.booted: [package_name]');`  | `None`                                                |
| `Hook::first('rakit.controller.factory',`      | `[string $class_name]);`                               |
| `Hook::first('rakit.config.loader',`           | `[string $package, string $file]);`                   |
| `Hook::first('rakit.language.loader',`         | `[string $package, string $language, string $file]);` |
| `Hook::until('rakit.view.loader',`             | `[string $package, string $view]);`                   |
| `Hook::until('rakit.view.engine',`             | `[View $view]);`                                      |
| `Hook::first('view.middleware',`               | `[string $content, string $path]);`                   |
| `Hook::fire('rakit.auth: login');`             | `None`                                                |
| `Hook::fire('rakit.auth: logout');`            | `None`                                                |
| `Hook::fire('facile.[event]',`                 | `[Facile $model]);`                                   |
| `Hook::fire('facile.[event]: [class_name]',`   | `[Facile $model]);`                                   |
| `Hook::fire('rakit.jobs.process',`             | `[object\|array $job]);`                              |
| `Hook::fire('rakit.jobs.run: [job_name]',`     | `$payloads` (spread as the listener's arguments)      |
| `Hook::fire('rakit.jobs.forget: [job_name]');` | `None`                                                |
| `Hook::first('404');`                          | `None`                                                |

> **Note on model events.** `[event]` is one of `retrieved`, `saving`, `creating`,
> `created`, `updating`, `updated`, `saved`, `deleting`, `deleted`, `restoring`,
> `restored` or `replicating`. Each of them is fired twice, once without the
> model name and once with it, so a listener may watch one model or all of them.
> A listener of `saving`, `creating`, `updating`, `deleting` or `restoring` that
> answers with `FALSE` calls the operation off. See
> [Model Events](/docs/database/facile#model-events).

> **Note on job events.** Job drivers fire `rakit.jobs.process` with the raw
> job record. `System\Job` listens to it and re-fires
> `rakit.jobs.run: [job_name]`, spreading the job's payloads as the listener's
> arguments — that is the event your `Jobable` classes are bound to.

> **Note on `404` and `500`.** Only `404` is fired by the framework (from the
> catch-all route and from the controller resolver). There is no `500` event;
> uncaught exceptions are handled by the debugger and rendered through
> `Response::error(500)`.
