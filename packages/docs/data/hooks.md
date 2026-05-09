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

To fire an event, just notify what event name you want to run:

#### Firing an event:

```php
$responses = Hook::fire('loaded');
```

Note that we store the result of the `fire()` method in the `$responses` variable. Because the `fire()` method will return an array containing responses from all listeners of the event.

Sometimes you want to fire an event, but only want to get the first response. Here's how:

#### Firing an event and getting only the first response:

```php
$response = Hook::first('loaded');
```

> The `first()` method will still run all listeners owned by the event, but only the first response will be returned.

While the `Hook::until()` method will run all listeners owned by the event, and will return the first response that is not `NULL`.

#### Firing an event and getting the first non-NULL response:

```php
$response = Hook::until('loaded');
```

<a id="listening-to-an-event"></a>

## Listening to an Event

But, what's the use of creating an event if it has no listeners? So, let's register an example listener that will be called when an event is fired:

#### Registering a listener to the event named `'loaded'`:

```php
Hook::listen('loaded', function () {
    // I will be called when the 'loaded' event is fired
});
```

The code you place inside the Closure above will be called when the `'loaded'` event is fired.

<a id="queued-events"></a>

## Queued Events

Sometimes you might just want to "queue" an event to be run in the future. You can do this via the `queue()` and `flush()` methods.

First, please specify the event name in the first parameter, remember! the name must be unique so as not to overlap:

#### Queuing an event:

```php
Hook::queue('foo', $user->id, [$user]);
```

This method accepts 3 parameters. The first is the queue name, the second is a unique name for this item in the queue, and the third is an array of data to pass to the flusher.

Next, we will register a flusher for the queue named `foo` above:

#### Registering an event flusher:

```php
Hook::flusher('foo', function ($key, $user) {
    // ...
});
```

Note that this flusher accepts 2 parameters. First, the unique name of the queued event, which in this case is the user ID. Then the second parameter (and the rest) will be the payload items for the event queue.

Finally, we can run the flusher and flush all queued events using the `flush()` method:

```php
Hook::flush('foo');
```

<a id="framework-events"></a>

## Framework Events

Here are some events that are run by default by Rakit:

#### Event fired when a package is booted:

```php
Hook::listen('rakit.booted: package', function () { });
```

#### Event fired when a database query is executed:

```php
Hook::listen('rakit.query', function ($sql, $bindings, $time) { });
```

#### Event fired just before a response is sent to the browser:

```php
Hook::listen('rakit.done', function ($response) { });
```

#### Event fired when a message is logged using the `Log` class:

```php
Hook::listen('rakit.log', function ($type, $message) { });
```

Here is the complete list of built-in framework events along with their parameters. You can listen to the following events if needed:

| Command                                         | Parameter                                             |
| ----------------------------------------------- | ----------------------------------------------------- |
| `Hook::fire('rakit.done',`                     | `[Response $response]);`                              |
| `Hook::fire('rakit.log',`                      | `[string $type, string $message]);`                   |
| `Hook::fire('rakit.query',`                    | `[string $sql, array $bindings, string $time]);`      |
| `Hook::fire('rakit.resolving',`                | `[string $type, mixed $object]);`                     |
| `Hook::fire('rakit.composing: [view_name]',`   | `[View $view]);`                                      |
| `Hook::fire('rakit.booted: [package_name]');`  | `None`                                                |
| `Hook::first('rakit.controller.factory',`      | `[string $className]);`                               |
| `Hook::first('rakit.config.loader',`           | `[string $package, string $file]);`                   |
| `Hook::first('rakit.language.loader',`         | `[string $package, string $language, string $file]);` |
| `Hook::until('rakit.view.loader',`             | `[string $package, string $view]);`                   |
| `Hook::until('rakit.view.engine',`             | `[View $view]);`                                      |
| `Hook::first('rakit.view.middleware',`         | `[string $content, string $path]);`                   |
| `Hook::first('rakit.auth: login');`            | `None`                                                |
| `Hook::first('rakit.auth: logout');`           | `None`                                                |
| `Hook::fire('facile.saving',`                  | `[Facile $model]);`                                   |
| `Hook::fire('facile.saving: [class_name]',`    | `[Facile $model]);`                                   |
| `Hook::fire('facile.updated',`                 | `[Facile $model]);`                                   |
| `Hook::fire('facile.updated: [class_name]',`   | `[Facile $model]);`                                   |
| `Hook::fire('facile.created',`                 | `[Facile $model]);`                                   |
| `Hook::fire('facile.created: [class_name]',`   | `[Facile $model]);`                                   |
| `Hook::fire('facile.saved',`                   | `[Facile $model]);`                                   |
| `Hook::fire('facile.saved: [class_name]',`     | `[Facile $model]);`                                   |
| `Hook::fire('facile.deleting',`                | `[Facile $model]);`                                   |
| `Hook::fire('facile.deleting: [class_name]',`  | `[Facile $model]);`                                   |
| `Hook::fire('facile.deleted',`                 | `[Facile $model]);`                                   |
| `Hook::fire('facile.deleted: [class_name]',`   | `[Facile $model]);`                                   |
| `Hook::fire('rakit.jobs.run: [job_name]',`     | `[string $name, array $payloads]);`                   |
| `Hook::fire('rakit.jobs.ran: [job_name]',`     | `[string $name, array $payloads]);`                   |
| `Hook::fire('rakit.jobs.forget: [job_name]');` | `None`                                                |
| `Hook::first('500');`                          | `None`                                                |
| `Hook::first('404');`                          | `None`                                                |