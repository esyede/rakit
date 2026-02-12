# Event & Listener

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Firing an Event](#firing-an-event)
-   [Listening to an Event](#listening-to-an-event)
-   [Queued Events](#queued-events)
-   [Framework Events](#framework-events)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Events provide a good way to decouple resources in your application, so that classes, libraries, or plugins do not get mixed up and are easy to monitor.

This also allows classes, libraries, and plugins to utilize your application's core without changing its code.

<a id="firing-an-event"></a>

## Firing an Event

To fire an event, just notify what event name you want to run:

#### Firing an event:

```php
$responses = Event::fire('loaded');
```

Note that we store the result of the `fire()` method in the `$responses` variable. Because the `fire()` method will return an array containing responses from all listeners of the event.

Sometimes you want to fire an event, but only want to get the first response. Here's how:

#### Firing an event and getting only the first response:

```php
$response = Event::first('loaded');
```

> The `first()` method will still run all listeners owned by the event, but only the first response will be returned.

While the `Event::until()` method will run all listeners owned by the event, and will return the first response that is not `NULL`.

#### Firing an event and getting the first non-NULL response:

```php
$response = Event::until('loaded');
```

<a id="listening-to-an-event"></a>

## Listening to an Event

But, what's the use of creating an event if it has no listeners? So, let's register an example listener that will be called when an event is fired:

#### Registering a listener to the event named `'loaded'`:

```php
Event::listen('loaded', function () {
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
Event::queue('foo', $user->id, [$user]);
```

This method accepts 3 parameters. The first is the queue name, the second is a unique name for this item in the queue, and the third is an array of data to pass to the flusher.

Next, we will register a flusher for the queue named `foo` above:

#### Registering an event flusher:

```php
Event::flusher('foo', function ($key, $user) {
    // ...
});
```

Note that this flusher accepts 2 parameters. First, the unique name of the queued event, which in this case is the user ID. Then the second parameter (and the rest) will be the payload items for the event queue.

Finally, we can run the flusher and flush all queued events using the `flush()` method:

```php
Event::flush('foo');
```

<a id="framework-events"></a>

## Framework Events

Here are some events that are run by default by Rakit:

#### Event fired when a package is booted:

```php
Event::listen('rakit.booted: package', function () { });
```

#### Event fired when a database query is executed:

```php
Event::listen('rakit.query', function ($sql, $bindings, $time) { });
```

#### Event fired just before a response is sent to the browser:

```php
Event::listen('rakit.done', function ($response) { });
```

#### Event fired when a message is logged using the `Log` class:

```php
Event::listen('rakit.log', function ($type, $message) { });
```

Here is the complete list of built-in framework events along with their parameters. You can listen to the following events if needed:

| Command                                         | Parameter                                             |
| ----------------------------------------------- | ----------------------------------------------------- |
| `Event::fire('rakit.done',`                     | `[Response $response]);`                              |
| `Event::fire('rakit.log',`                      | `[string $type, string $message]);`                   |
| `Event::fire('rakit.query',`                    | `[string $sql, array $bindings, string $time]);`      |
| `Event::fire('rakit.resolving',`                | `[string $type, mixed $object]);`                     |
| `Event::fire('rakit.composing: [view_name]',`   | `[View $view]);`                                      |
| `Event::fire('rakit.booted: [package_name]');`  | `None`                                                |
| `Event::first('rakit.controller.factory',`      | `[string $className]);`                               |
| `Event::first('rakit.config.loader',`           | `[string $package, string $file]);`                   |
| `Event::first('rakit.language.loader',`         | `[string $package, string $language, string $file]);` |
| `Event::until('rakit.view.loader',`             | `[string $package, string $view]);`                   |
| `Event::until('rakit.view.engine',`             | `[View $view]);`                                      |
| `Event::first('rakit.view.middleware',`         | `[string $content, string $path]);`                   |
| `Event::first('rakit.auth: login');`            | `None`                                                |
| `Event::first('rakit.auth: logout');`           | `None`                                                |
| `Event::fire('facile.saving',`                  | `[Facile $model]);`                                   |
| `Event::fire('facile.saving: [class_name]',`    | `[Facile $model]);`                                   |
| `Event::fire('facile.updated',`                 | `[Facile $model]);`                                   |
| `Event::fire('facile.updated: [class_name]',`   | `[Facile $model]);`                                   |
| `Event::fire('facile.created',`                 | `[Facile $model]);`                                   |
| `Event::fire('facile.created: [class_name]',`   | `[Facile $model]);`                                   |
| `Event::fire('facile.saved',`                   | `[Facile $model]);`                                   |
| `Event::fire('facile.saved: [class_name]',`     | `[Facile $model]);`                                   |
| `Event::fire('facile.deleting',`                | `[Facile $model]);`                                   |
| `Event::fire('facile.deleting: [class_name]',`  | `[Facile $model]);`                                   |
| `Event::fire('facile.deleted',`                 | `[Facile $model]);`                                   |
| `Event::fire('facile.deleted: [class_name]',`   | `[Facile $model]);`                                   |
| `Event::fire('rakit.jobs.run: [job_name]',`     | `[string $name, array $payloads]);`                   |
| `Event::fire('rakit.jobs.ran: [job_name]',`     | `[string $name, array $payloads]);`                   |
| `Event::fire('rakit.jobs.forget: [job_name]');` | `None`                                                |
| `Event::first('500');`                          | `None`                                                |
| `Event::first('404');`                          | `None`                                                |