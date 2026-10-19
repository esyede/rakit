# Transformers

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Writing a Transformer](#writing-a-transformer)
- [Lists of Resources](#lists-of-resources)
- [Paginated Lists](#paginated-lists)
- [Conditional Keys](#conditional-keys)
- [Nesting](#nesting)
- [Data Beside the Data](#data-beside-the-data)
- [The Wrapper](#the-wrapper)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

A transformer sits between a model and the JSON your API returns. It decides exactly which keys go out and in what shape, so renaming a database column doesn't silently change your API.

The simplest use needs no class at all:

```php
return Transformer::make($user)->to_response();
```

```json
{
    "data": {
        "id": 1,
        "name": "John",
        "email": "john@site.com"
    }
}
```

Without a custom class, the transformer returns the resource's `to_array()`, so a Facile model still honours its `$hidden`, `$visible`, `$appends` and `$casts`.

<a id="writing-a-transformer"></a>

## Writing a Transformer

Transformers live in `application/transformers/` and are named after the file with a `_Transformer` suffix (like `User_Controller`), so the autoloader finds them. Generate one with `php rakit make:transformer user`. Package transformers carry the package name as a prefix: `Blog_Post_Transformer` lives in `packages/blog/transformers/post.php`.

```php
// application/transformers/user.php

class User_Transformer extends Transformer
{
    public function to_array()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'registered' => $this->created_at,
        ];
    }
}
```

The resource's attributes and methods are reachable through `$this` (and via array access, `$this['name']`). Dates arrive as the raw database string unless the model casts them:

```php
class User extends Facile
{
    public static $casts = ['created_at' => 'datetime'];
}

// in the transformer:
'registered' => $this->created_at->format('d/m/Y'),
```

`make($user)` and `new User_Transformer($user)` are equivalent. To output the result:

| Method | Returns |
| --- | --- |
| `to_response($status = 200, $headers = [])` | A JSON `Response` |
| `to_json($options = 0)` | A JSON string |
| `resolve()` | The final array, wrapper included |

A transformer also converts itself to JSON when passed to `json_encode()` or cast to a string.

<a id="lists-of-resources"></a>

## Lists of Resources

`collection()` transforms every item with the transformer it was called on. It accepts an array, a `Collection`, a `Paginator`, or anything traversable:

```php
return User_Transformer::collection(User::all())->to_response();
```

```json
{
    "data": [
        { "id": 1, "name": "John" },
        { "id": 2, "name": "Jane" }
    ]
}
```

<a id="paginated-lists"></a>

## Paginated Lists

Pass a paginator and the pagination links and counts are added automatically:

```php
return User_Transformer::collection(User::paginate(15))->to_response();
```

```json
{
    "data": [ .. ],
    "links": {
        "first": "http://site.com/users?page=1",
        "last": "http://site.com/users?page=4",
        "prev": null,
        "next": "http://site.com/users?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 4,
        "path": "http://site.com/users",
        "per_page": 15,
        "to": 15,
        "total": 60
    }
}
```

<a id="conditional-keys"></a>

## Conditional Keys

`when()` includes a key only when the condition is true; otherwise the key is omitted entirely (not set to `null`). The value may be a Closure, which only runs when needed. An optional third argument is used as the value when the condition is false:

```php
public function to_array()
{
    return [
        'id' => $this->id,
        'email' => $this->when(Auth::user()->admin, $this->email),
        'phone' => $this->when($is_admin, $this->phone, 'hidden'),
        'stats' => $this->when($detailed, function () {
            return $this->calculate_stats();
        }),
    ];
}
```

`merge_when()` includes or omits a whole group of keys; `merge()` always includes them:

```php
return [
    'id' => $this->id,
    $this->merge_when(Auth::user()->admin, [
        'email' => $this->email,
        'last_ip' => $this->last_ip,
    ]),
];
```

<a id="nesting"></a>

## Nesting

A transformer nested inside another contributes only its data, without its own wrapper:

```php
public function to_array()
{
    return [
        'title' => $this->title,
        'author' => User_Transformer::make($this->author),
        'comments' => Comment_Transformer::collection($this->comments),
    ];
}
```

```json
{
    "data": {
        "title": "Learning Rakit",
        "author": { "id": 1, "name": "John" },
        "comments": [{ "id": 9, "body": "Great!" }]
    }
}
```

<a id="data-beside-the-data"></a>

## Data Beside the Data

`additional()` adds top-level keys next to the wrapped data at the call site; override `with()` to add them to every response of that transformer:

```php
return User_Transformer::make($user)
    ->additional(['version' => '1.0'])
    ->to_response();

// or, inside the transformer:
public function with()
{
    return ['version' => '1.0'];
}
```

```json
{
    "data": { "id": 1, "name": "John" },
    "version": "1.0"
}
```

<a id="the-wrapper"></a>

## The Wrapper

Data is wrapped in a `data` key by default. Override `$wrap` to rename it, or set it to `null` to return the data unwrapped (anything from `with()` or `additional()` is then merged into the data itself):

```php
class User_Transformer extends Transformer
{
    public static $wrap = 'user'; // or null
}
```

To disable wrapping for all transformers, call this while the application boots:

```php
// application/boot.php

Transformer::without_wrapping();
```

> Collections read `$wrap` from the base `Transformer` class, so a `$wrap` set on `User_Transformer` does not change the key used by `User_Transformer::collection()`.
