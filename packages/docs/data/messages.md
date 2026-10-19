# Messages

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Introduction](#introduction)
- [Creating a Bag](#creating-a-bag)
- [Adding Messages](#adding-messages)
- [Checking Messages](#checking-messages)
- [Retrieving Messages](#retrieving-messages)
  - [Formatting](#formatting)
- [Bag Helpers](#bag-helpers)
- [Displaying Validation Errors](#displaying-validation-errors)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>
## Introduction

`Messages` is a bag of messages grouped by key, such as validation errors or notifications.
The [validator](/docs/validation) fills one for you, but it works on its own too.

<a id="creating-a-bag"></a>
<a id="creating-instance"></a>
## Creating a Bag

```php
use System\Messages;

$messages = new Messages();

$messages = new Messages([
    'email' => ['Invalid email'],
]);
```

<a id="adding-messages"></a>
## Adding Messages

A key may hold several messages; adding the same message twice to one key is ignored.

```php
$messages->add('email', 'The email is invalid.');
$messages->add('email', 'The email is already registered.');
$messages->add('password', 'The password is too short.');
```

<a id="checking-messages"></a>
## Checking Messages

```php
$messages->has('email');   // the key has a message
$messages->any();          // the bag holds any message
```

<a id="retrieving-messages"></a>
<a id="first-message"></a>
<a id="all-messages-from-key"></a>
<a id="all-messages"></a>
## Retrieving Messages

```php
$messages->first();          // first message of any key ('' when empty)
$messages->first('email');   // first message of one key
$messages->get('email');     // every message of one key (array)
$messages->all();            // every message of every key, as a flat array
```

<a id="formatting"></a>
<a id="format-output"></a>
### Formatting

Each retrieval method takes an optional format, where `:message` is replaced by the message:

```php
$messages->first('email', '<p class="error">:message</p>');
$messages->get('email', '<li>:message</li>');
$messages->all('<li>:message</li>');
```

`format()` sets the default format for every later retrieval:

```php
$messages->format('<div class="alert">:message</div>');

echo $messages->first('email'); // <div class="alert">The email is invalid.</div>
```

<a id="bag-helpers"></a>
## Bag Helpers

The bag can be counted, iterated, used as an array and JSON-encoded:

```php
count($messages);                 // number of keys holding a message
$messages['email'];               // same as get('email')
isset($messages['email']);
$messages['email'] = 'required';  // same as add()
unset($messages['email']);        // same as forget()

foreach ($messages as $key => $items) {
    // $items is the array of messages for $key
}

json_encode($messages);           // {"email":["required"]}
```

Other helpers:

```php
$messages->keys();                       // every key holding a message
$messages->has_any(['email', 'name']);   // at least one key has a message
$messages->is_empty();
$messages->is_not_empty();
$messages->merge($other);                // merge another bag or a plain array
$messages->forget('email');              // drop every message of a key
$messages->to_array();                   // raw messages, keyed by field
$messages->to_json();                    // the same, as JSON
```

<a id="displaying-validation-errors"></a>
<a id="usage-with-validation"></a>
## Displaying Validation Errors

After validation, the bag sits in `$validation->errors`. Every view also receives an `$errors`
bag (empty when there are none), filled from the session after a redirect
`->with_errors($validation)`:

```blade
@if ($errors->has('email'))
    <span class="error">{{ $errors->first('email') }}</span>
@endif

@if ($errors->any())
    <ul>
        @foreach ($errors->all('<li>:message</li>') as $error)
            {!! $error !!}
        @endforeach
    </ul>
@endif
```
