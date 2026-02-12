# Messages

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Creating Instance](#creating-instance)
- [Adding Messages](#adding-messages)
- [Checking Messages](#checking-messages)
- [Retrieving Messages](#retrieving-messages)
  - [First Message](#first-message)
  - [All Messages From Key](#all-messages-from-key)
  - [All Messages](#all-messages)
- [Format Output](#format-output)
- [Usage With Validation](#usage-with-validation)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The `Messages` class provides an easy way to collect and display messages. This class is very useful for displaying error messages, notifications, or any other type of messages.

This class is automatically used by the validation system to store error messages, but you can also use it manually for other purposes.

<a id="creating-instance"></a>
## Creating Instance

You can create a `Messages` instance with or without initial data:

```php
use System\Messages;

// Empty instance
$messages = new Messages();

// Instance with initial data
$messages = new Messages([
    'email' => ['Invalid email'],
    'password' => ['Password must be at least 6 characters'],
]);
```

<a id="adding-messages"></a>
## Adding Messages

Use the `add()` method to add messages to the collector:

```php
$messages = new Messages();

// Add message for 'email' key
$messages->add('email', 'The email you entered is invalid.');

// Add another message for the same key
$messages->add('email', 'Email is already registered.');

// Add message for different key
$messages->add('password', 'Password must be at least 6 characters.');
```

> **Note:** The `add()` method will automatically prevent duplication of messages for the same key.

<a id="checking-messages"></a>
## Checking Messages

You can check if there are messages for a specific key or if there are any messages at all:

```php
// Check if there are messages for 'email'
if ($messages->has('email')) {
    // There are error messages for email
}

// Check if there are any messages (not empty)
if ($messages->any()) {
    // There are messages
}
```

<a id="retrieving-messages"></a>
## Retrieving Messages

<a id="first-message"></a>
### First Message

The `first()` method retrieves the first message from the given key, or the first message from all keys if no parameter is provided:

```php
// Retrieve the first message from all keys
echo $messages->first();

// Retrieve the first message from 'email' key
echo $messages->first('email');

// Retrieve the first message with custom format
echo $messages->first('email', '<p class="error">:message</p>');
```

<a id="all-messages-from-key"></a>
### All Messages From Key

The `get()` method retrieves all messages from the specified key:

```php
// Retrieve all messages from 'email' key
$email_errors = $messages->get('email');

// Output:
// [
//     'The email you entered is invalid.',
//     'Email is already registered.'
// ]

// With custom format
$email_errors = $messages->get('email', '<li>:message</li>');

// Output:
// [
//     '<li>The email you entered is invalid.</li>',
//     '<li>Email is already registered.</li>'
// ]
```

<a id="all-messages"></a>
### All Messages

The `all()` method retrieves all messages from all keys:

```php
// Retrieve all messages
$all_errors = $messages->all();

// With custom format
$all_errors = $messages->all('<p class="alert">:message</p>');
```

<a id="format-output"></a>
## Format Output

You can set the default format for all messages using the `format()` method:

```php
$messages = new Messages();

// Set default format
$messages->format('<div class="alert alert-danger">:message</div>');

// Now all messages will use this format
$messages->add('email', 'Invalid email');
echo $messages->first('email');

// Output: <div class="alert alert-danger">Invalid email</div>
```

The `:message` placeholder will be replaced with the actual message.

<a id="usage-with-validation"></a>
## Usage With Validation

The `Messages` class is automatically used by the validation system. When validation fails, you can access error messages through the `$errors` property:

```php
$validation = Validator::make(Input::all(), [
    'email' => 'required|email',
    'password' => 'required|min:6',
]);

if ($validation->fails()) {
    // Access Messages instance
    $errors = $validation->errors;

    // Display first message
    echo $errors->first('email');

    // Display all email messages
    foreach ($errors->get('email') as $message) {
        echo $message;
    }

    // Display all messages
    foreach ($errors->all() as $message) {
        echo $message;
    }
}
```

Example usage in view:

```php
<?php if ($errors->has('email')): ?>
    <div class="alert alert-danger">
        <?php echo $errors->first('email'); ?>
    </div>
<?php endif; ?>

<!-- Or display all errors -->
<?php if ($errors->any()): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors->all('<li>:message</li>') as $error): ?>
                <?php echo $error; ?>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
```

With Blade template:

```blade
@if($errors->has('email'))
    <div class="alert alert-danger">
        {{ $errors->first('email') }}
    </div>
@endif

<!-- Or display all errors -->
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all('<li>:message</li>') as $error)
                {!! $error !!}
            @endforeach
        </ul>
    </div>
@endif
```
