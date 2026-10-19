# Container

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Registering Objects](#registering-objects)
-   [Resolving Objects](#resolving-objects)
-   [Automatic Resolution](#automatic-resolution)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The Container is a service locator: you tell it once how to build an object under
a name, then resolve that name anywhere to get a ready instance. It is handy for
keeping complex construction (a mailer, an SDK client) in one place, and for
swapping in a fake implementation under the same name in tests.

<a id="registering-objects"></a>

## Registering Objects

`register()` stores a resolver that runs on **every** resolve:

```php
Container::register('mailer', function () {
    $transport = Swift_MailTransport::newInstance();

    return Swift_Mailer::newInstance($transport);
});
```

`singleton()` runs the resolver once, on the first resolve, and returns that same
instance afterwards. `instance()` registers an object you already have:

```php
Container::singleton('mailer', function () {
    // ..
});

Container::instance('mailer', $instance);
```

The resolver may also be a class name, which makes the name an alias of that class:

```php
Container::register('mailer', 'App\Mail\Mailer');
```

<a id="resolving-objects"></a>

## Resolving Objects

```php
$mailer = Container::resolve('mailer');

// Arguments are passed to the resolver
$mailer = Container::resolve('mailer', ['test@example.com']);

if (Container::registered('mailer')) {
    // 'mailer' is registered
}
```

Every resolve fires the `rakit.resolving` event with the name and the object.
`Container::flush()` forgets the resolved singletons (used by
[workers](/docs/workers) between requests).

<a id="automatic-resolution"></a>

## Automatic Resolution

A class name that was never registered is built through reflection: constructor
parameters type-hinted with a class are resolved from the container, the others
take their default value. Arguments passed to `resolve()` fill the first
parameters.

```php
class Report
{
    public function __construct(Mailer $mailer, $title = 'Daily')
    {
        // ..
    }
}

$report = Container::resolve('Report'); // Mailer is resolved automatically
```

A parameter with neither a class type nor a default throws an exception, as does
a circular dependency.

> You can also [register controllers to the container](/docs/controllers#dependency-injection).
