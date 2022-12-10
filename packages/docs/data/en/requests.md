# Memeriksa Request

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Working With The URI](#bekerja-dengan-uri)
-   [Other Request Helpers](#helper-lainnya)

<!-- /MarkdownTOC -->

<a id="bekerja-dengan-uri"></a>

## Working With The URI

#### Getting the current URI for the request:

```php
echo URI::current();
```

#### Getting a specific segment from the URI:

```php
echo URI::segment(1);
```

#### Returning a default value if the segment doesn't exist:

```php
echo URI::segment(10, 'Foo');
```

#### Getting the full request URI, including query string:

```php
echo URI::full();
```

Sometimes you may need to determine if the current URI is a given string,
or begins with a given string. Here's an example of how you can use
the `is()` method to accomplish this:

#### Determine if the URI is 'home':

```php
if (URI::is('home')) {
    // The current URI is: home
}
```

#### Determine if the current URI begins with 'docs/':

```php
if (URI::is('docs/*')) {
    // The current URI begins with: 'docs/'
}
```

<a id="helper-lainnya"></a>

## Other Request Helpers

#### Getting the current request method:

```php
echo Request::method();
```

#### Accessing the `$_SERVER` global array:

```php
echo Request::server('http_referer');
```

#### Retrieving the requester's IP address:

```php
echo Request::ip();
```

#### Determining if the current request is using HTTPS:

```php
if (Request::secure()) {
	// RThis request is over HTTPS!
}
```

#### Determining if the current request is an AJAX request:

```php
if (Request::ajax()) {
	// This request is using AJAX!
}
```

#### Determining if the current requst is via the CLI console:

```php
if (Request::cli()) {
	// This request came from the CLI!
}
```
