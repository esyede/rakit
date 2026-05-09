# Container

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Registering Objects](#registering-objects)
-   [Resolving Objects](#resolving-objects)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The Container is a service locator: you teach it how to build an object once
(under a name), and resolve that name anywhere in the app to get a ready
instance.

This is mostly useful for two things:

1.  **Centralizing complex construction.** Build a mailer, queue, or external
    SDK in one place — every caller gets the same recipe.
2.  **Swapping implementations in tests.** Register a fake implementation under
    the same name to isolate the code under test from real services. See
    [stubs and mocks](http://martinfowler.com/articles/mocksArentStubs.html)
    for the broader pattern.

<a id="registering-objects"></a>

## Registering Objects

#### Registering a resolver to the container:

```php
Container::register('mailer', function () {
    $transport = Swift_MailTransport::newInstance();

    return Swift_Mailer::newInstance($transport);
});
```

Great! Now we have registered a resolver for SwiftMailer to our container.
However, what if we don't want the container to create a new `mailer` instance every time we need it?

Maybe we only want the container to return the same instance after the initial instance is created.
Easy, just tell the container that the object should be a singleton:

#### Registering a singleton object to the container:

```php
Container::singleton('mailer', function () {
    // ..
});
```

You can also register an existing object instance as a singleton to the container.

#### Registering an existing instance to the container:

```php
Container::instance('mailer', $instance);
```

<a id="resolving-objects"></a>

## Resolving Objects

After SwiftMailer is registered to the container, we can easily resolve it:

```php
$mailer = Container::resolve('mailer');
```

You can also pass parameters to the resolver when resolving the object:

#### Resolving object with parameters:

```php
$mailer = Container::resolve('mailer', ['test@example.com']);
```

#### Checking if an object is already registered:

```php
if (Container::registered('mailer')) {
    // Object 'mailer' is already registered
}
```

> You can also [register controllers to the container](/docs/controllers#dependency-injection).
