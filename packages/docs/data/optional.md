# Optional

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Properties and Methods](#properties-and-methods)
-   [Array Access](#array-access)
-   [Using a Callback](#using-a-callback)
-   [Example](#example)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>
<a id="creating-instance"></a>
<a id="helper-function"></a>

## Introduction

`System\Optional` wraps a value that might be `null`, so you can read its
properties, call its methods, or access its keys without a null check. Anything
read from a missing value returns `null` instead of an error.

Create one with the `optional()` helper or the constructor:

```php
$user = optional(User::find($id));

$user = new \System\Optional(User::find($id));
```

<a id="properties-and-methods"></a>
<a id="accessing-property"></a>
<a id="calling-method"></a>

## Properties and Methods

```php
$user = optional(User::find($id));

echo $user->name;                   // the name, or null if no user
$posts = $user->posts();            // the method result, or null if no user
$user->update_profile('New Name');  // arguments are passed through
```

Properties are read only when the wrapped value is an object and `isset()` is true
for that property, otherwise you get `null`. Methods are called only when the value
is an object.

<a id="array-access"></a>

## Array Access

`Optional` implements `ArrayAccess`. Reading a key uses
[Arr::get()](/docs/arrays), so dot notation works and missing keys return `null`:

```php
$data = optional(json_decode($response, true));

echo $data['data.user.name'];   // null if any level is missing

isset($data['data']);           // key check (no dot notation)
$data['email'] = 'john@example.com';
unset($data['email']);
```

Setting and unsetting do nothing when the wrapped value is not an array or
`ArrayAccess` object, e.g. `null`.

<a id="using-a-callback"></a>

## Using a Callback

Passing a callback to `optional()` returns the callback's result instead of an
`Optional` instance. The callback **always runs**, even when the value is `null`,
so check it yourself:

```php
$name = optional(User::find($id), function ($user) {
    return $user ? strtoupper($user->name) : null;
});
```

<a id="example"></a>
<a id="use-cases"></a>
<a id="avoiding-null-pointer"></a>
<a id="api-response"></a>
<a id="database-query"></a>

## Example

Each step that might return `null` needs its own wrapper:

```php
// Instead of nested if ($user) { if ($user->profile) { ... } }
$city = optional(optional(optional(User::find($id))->profile)->address)->city;

$email = optional(User::find($id))->email ?: 'no-email@example.com';

if (optional($user)->is_admin) {
    // ...
}
```

> Long chains of `optional()` get hard to read and debug. Handling `null`
> explicitly is often clearer.
