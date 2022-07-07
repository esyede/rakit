# Event & Listener

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#pengetahuan-dasar)
- [Firing Events](#menjalankan-event)
- [Listening To Events](#me-listen-sebuah-event)
- [Queued Events](#antrian-event)
- [Framework Events](#framework-event)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Basic Knowledge

Events provide a great way to de-couple functionalities in your application,
so classes, libraries or plugins won't get mixed up and easy to monitor.


It also allows classes, libraries and plugins to take advantage of the core of your application
without modifying its code.



<a id="menjalankan-event"></a>
## Firing Events

To fire an event, just tell the name of the event you want to fire:

#### Menjalankan sebuah event:

```php
$responses = Event::fire('loaded');
```

Perhatikan bahwa kami menyimpan hasil dari metode `fire()` ke variabel `$responses`. Karena
method `fire()` ini akan mereturn array yang berisi response dari semua listener milik si event.
Notice that we assign result of the `fire()` method to the `$responses` variable.
This `fire()` method will return an array containing the responses of all listeners belonging to the event.


Sometimes you may want to fire an event, but only need the first response. Here's how:


#### Menjalankan sebuah event dan hanya mengambil response pertama saja:

```php
$response = Event::first('loaded');
```

>  This `first()` method will still fire all of the handlers listening to the event,
    but will only return the first response.

While the `Event::until()` method will execute the event handlers until
the first non-`NULL` response is returned.


#### Firing an event until the first non-null response:

```php
$response = Event::until('loaded');
```


<a id="me-listen-sebuah-event"></a>
## Listening To Events

So, what good are events if nobody is listening? Register an event handler that
will be called when an event fires:

#### Registering an event handler:

```php
Event::listen('loaded', function () {
    // I will be triggered when 'loaded' event is executed

});
```

The code you put in the Closure above will be called when the `'loaded'` event is executed.



<a id="antrian-event"></a>
## Queued Events

Sometimes you may wish to _"queue"_ an event for firing, but not fire it immediately.
You can do this via the `queue()` and `flush()` methods.


First, throw an event on a given queue with a unique identifier:

#### Registering a queued event:

```php
Event::queue('foo', $user->id, [$user]);
```

This method accepts three parameters. The first is the name of the queue,
the second is a unique identifier for this item on the queue,
and the third is an array of data to pass to the queue flusher.

Next, we'll register a flusher for the `'foo'` queue:

#### Registering an event flusher:

```php
Event::flusher('foo', function ($key, $user) {
    // ...
});
```

Note that the event flusher receives two arguments.
The first, is the unique identifier for the queued event,
which in this case would be the user's ID.
The second (and any remaining) parameters would be the payload items for the queued event.

Finally, we can run our flusher and flush all queued events using the `flush()` method:

```php
Event::flush('foo');
```

<a id="framework-event"></a>
## Framework Events

There are several events that are fired by the the framework. Here they are:

#### Event fired when a package is booted:

```php
Event::listen('rakit.booted: package', function () { });
```

#### Event fired when a database query is executed:

```php
Event::listen('rakit.query', function ($sql, $bindings, $time) { });
```

#### Event fired right before response is sent to browser:

```php
Event::listen('rakit.done', function ($response) { });
```

#### Event fired when a messaged is logged using the `Log` class:

```php
Event::listen('rakit.log', function ($type, $message) { });
```


The following is a complete list of famework default events and their parameters.
You can listen to the following events when needed:


| Command                                         | Parameter                                              |
| ----------------------------------------------- | -----------------------------------------------------  |
| `Event::fire('rakit.done',`                     | `[Response $response]);`                               |
| `Event::fire('rakit.log',`                      | `[string $type, string $message]);`                    |
| `Event::fire('rakit.query',`                    | `[string $sql, array $bindings, string $time]);`       |
| `Event::fire('rakit.resolving',`                | `[string $type, mixed $object]);`                      |
| `Event::fire('rakit.composing: [view_name]',`   | `[View $view]);`                                       |
| `Event::fire('rakit.booted: [package_name]');`  | `None`                                                 |
| `Event::first('rakit.controller.factory',`      | `[string $className]);`                                |
| `Event::first('rakit.config.loader',`           | `[string $package, string $file]);`                    |
| `Event::first('rakit.language.loader',`         | `[string $package, string $language, string $file]);`  |
| `Event::until('rakit.view.loader',`             | `[string $package, string $view]);`                    |
| `Event::until('rakit.view.engine',`             | `[View $view]);`                                       |
| `Event::first('rakit.view.middleware',`         | `[string $content, string $path]);`                    |
| `Event::first('rakit.auth: login');`            | `None`                                                 |
| `Event::first('rakit.auth: logout');`           | `None`                                                 |
| `Event::fire('facile.saving',`                  | `[Facile $model]);`                                    |
| `Event::fire('facile.saving: [class_name]',`    | `[Facile $model]);`                                    |
| `Event::fire('facile.updated',`                 | `[Facile $model]);`                                    |
| `Event::fire('facile.updated: [class_name]',`   | `[Facile $model]);`                                    |
| `Event::fire('facile.created',`                 | `[Facile $model]);`                                    |
| `Event::fire('facile.created: [class_name]',`   | `[Facile $model]);`                                    |
| `Event::fire('facile.saved',`                   | `[Facile $model]);`                                    |
| `Event::fire('facile.saved: [class_name]',`     | `[Facile $model]);`                                    |
| `Event::fire('facile.deleting',`                | `[Facile $model]);`                                    |
| `Event::fire('facile.deleting: [class_name]',`  | `[Facile $model]);`                                    |
| `Event::fire('facile.deleted',`                 | `[Facile $model]);`                                    |
| `Event::fire('facile.deleted: [class_name]',`   | `[Facile $model]);`                                    |
| `Event::first('500');`                          | `None`                                                 |
| `Event::first('404');`                          | `None`                                                 |
