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

A transformer stands between a model and the JSON that goes out. It says exactly
which keys the response carries, and in what shape, so a column renamed in the
database does not silently change the API your clients depend on.

Rakit's is `Transformer`. It is what Laravel calls an API Resource.

The quickest use needs no class at all:

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

Asked for nothing in particular, a transformer hands over `to_array()` of the
resource it was given, so a Facile model still honours its `$hidden`, `$visible`,
`$appends` and `$casts`.

<a id="writing-a-transformer"></a>

## Writing a Transformer

Put the class in `application/transformers/` and give it a `to_array()`:

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

The attributes and the methods of the resource are reachable straight from
`$this`, so `$this->name` reads the model. A date arrives as the string the
database holds unless the model casts it, so ask for the cast when the
transformer needs to format it:

```php
class User extends Facile
{
    public static $casts = ['created_at' => 'datetime'];
}

// then, in the transformer:
'registered' => $this->created_at->format('d/m/Y'),
```

Then:

```php
return User_Transformer::make($user)->to_response();
```

`make()` and `new User_Transformer($user)` are the same thing. Besides
`to_response()`, a transformer answers `to_json()`, `resolve()` for the plain
array, and turns into JSON on its own when handed to `json_encode()`.

<a id="lists-of-resources"></a>

## Lists of Resources

`collection()` hands every item to a transformer of its own:

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

It takes an array, a `Collection`, a `Paginator`, or anything walkable.

<a id="paginated-lists"></a>

## Paginated Lists

Hand it a paginator and the links and the counts come along:

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

`when()` keeps a key only when the condition holds. A key that is left out does
not appear as `null`, it does not appear at all:

```php
public function to_array()
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->when(Auth::user()->admin, $this->email),
    ];
}
```

Hand it a closure when working out the value costs something. The closure only
runs when the condition holds:

```php
'stats' => $this->when($detailed, function () {
    return $this->calculate_stats();
}),
```

A third argument is the value to use when the condition does not hold, `null`
included:

```php
'email' => $this->when($isAdmin, $this->email, 'hidden'),
```

`merge_when()` folds a whole set of keys in, or leaves all of them out:

```php
public function to_array()
{
    return [
        'id' => $this->id,
        $this->merge_when(Auth::user()->admin, [
            'email' => $this->email,
            'last_ip' => $this->last_ip,
        ]),
    ];
}
```

`merge()` is the same thing without a condition.

<a id="nesting"></a>

## Nesting

A transformer inside another one contributes its keys, not a wrapper of its own:

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

`additional()` puts something at the top level, beside the wrapped data:

```php
return User_Transformer::make($user)
    ->additional(['version' => '1.0'])
    ->to_response();
```

```json
{
    "data": { "id": 1, "name": "John" },
    "version": "1.0"
}
```

`with()` does the same from inside the transformer, for what every response of
that kind should carry:

```php
class User_Transformer extends Transformer
{
    public function with()
    {
        return ['version' => '1.0'];
    }
}
```

<a id="the-wrapper"></a>

## The Wrapper

The data is wrapped in a `data` key. Give the transformer a `$wrap` of its own to
change it:

```php
class User_Transformer extends Transformer
{
    public static $wrap = 'user';
}
```

Set it to `NULL` to hand the data over bare. Anything from `with()` or
`additional()` is then merged into the data itself:

```php
class User_Transformer extends Transformer
{
    public static $wrap = null;
}
```

To drop the wrapper for every transformer at once, say so while the application
boots:

```php
// application/boot.php

Transformer::without_wrapping();
```
