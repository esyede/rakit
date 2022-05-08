# Container

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#pengetahuan-dasar)
- [Registering Object](#mendaftarkan-object)
- [Resolving Object](#me-resolve-object)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Basic Knowledge

Containers are just a way of managing object creation. You can use it to define the creation of complex objects,
allowing you to resolve them across your entire application with just one line of code.
You can also use it to _'inject'_ dependencies into your classes and controller.

Containers will help to make your application more flexible and testable.
Since you can list an alternative implementation of this bia container interface,
you can isolate the code you are testing from external dependencies
using the [stub and mocking](http://martinfowler.com/articles/mocksArentStubs.html) technique.


<a id="mendaftarkan-object"></a>
## Registering Object


#### Registering a resolver to the container:


```php
Container::register('mailer', function () {
    $transport = Swift_MailTransport::newInstance();

    return Swift_Mailer::newInstance($transport);
});
```

Excellent! We have now registered the resolver for SwiftMailer to our container.
However, what if we don't want the container to create a new `mailer` instance every time we need it?

Maybe we just want the container to return the same instance after the initial instance is created.
It's easy, just tell the container that the object must be singleton:


#### Mendaftarkan singleton object ke container:

```php
Container::singleton('mailer', function () {
    // ..
});
```

You can also register a pre-existing object instance as a singleton to the container.


#### Mendaftarkan instance yang ada ke container:

```php
Container::instance('mailer', $instance);
```

<a id="me-resolve-object"></a>
## Resolving Object

Once SwiftMailer is registered to the container, we can easily resolve it:


```php
$mailer = Container::resolve('mailer');
```

>  You can also [registers controller to the container](/docs/en/controllers#dependency-injection).

