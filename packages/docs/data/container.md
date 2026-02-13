# Container

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Registering Objects](#registering-objects)
-   [Resolving Objects](#resolving-objects)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The Container is just a way to manage object creation. You can use it to define the creation of complex objects,
allowing you to resolve them throughout your application with just one line of code.
You can also use it to _inject_ dependencies into your classes and controllers.

The Container helps make your application more flexible and easier to test.
Since you can register alternative implementations of interfaces via this container,
you can isolate the code you are testing from external dependencies
using [stub and mocking](http://martinfowler.com/articles/mocksArentStubs.html) techniques.

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
