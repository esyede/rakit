# Optional

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Creating Instance](#creating-instance)
- [Accessing Property](#accessing-property)
- [Calling Method](#calling-method)
- [Array Access](#array-access)
- [Use Cases](#use-cases)
  - [Avoiding Null Pointer](#avoiding-null-pointer)
  - [API Response](#api-response)
  - [Database Query](#database-query)
- [Helper Function](#helper-function)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The `Optional` class provides an elegant way to handle values that might be `null` without needing to manually check for `null`. This class implements the "Null Object" pattern, allowing you to access properties and methods of an object without worrying about "Trying to get property of non-object" errors.

When you access a property or method from an object that is `null` through `Optional`, this class will return `null` instead of an error.

<a id="creating-instance"></a>
## Creating Instance

You can create an `Optional` instance in several ways:

```php
// Directly with constructor
$optional = new Optional($value);

// Using helper function optional()
$optional = optional($value);
```

<a id="accessing-property"></a>
## Accessing Property

With `Optional`, you can access object properties safely without worrying about `null`:

```php
// Without Optional - prone to error
$user = User::find(1);
$name = $user->name; // Error if $user is null

// With Optional - safe
$user = optional(User::find(1));
$name = $user->name; // Returns null if $user is null, not error
```

Detailed example:

```php
// User found
$user = User::find(1); // User object
$optional = optional($user);
echo $optional->name; // "John Doe"
echo $optional->email; // "john@example.com"

// User not found
$user = User::find(999); // null
$optional = optional($user);
echo $optional->name; // null (not error)
echo $optional->email; // null (not error)
```

<a id="calling-method"></a>
## Calling Method

You can also call methods from the object safely:

```php
// Without Optional
$user = User::find(1);
if ($user) {
    $posts = $user->posts();
}

// With Optional
$user = optional(User::find(1));
$posts = $user->posts(); // Returns null if $user is null
```

Example with method that accepts parameters:

```php
$user = optional(User::find(1));

// Calling method with parameters
$result = $user->update_profile('New Name', 'new@email.com');

// If $user is null, method will not be called and returns null
```

<a id="array-access"></a>
## Array Access

The `Optional` class implements `ArrayAccess`, so you can access it like an array:

```php
$data = optional(['name' => 'John', 'age' => 30]);

// Access like array
echo $data['name']; // "John"
echo $data['age']; // 30
echo $data['email']; // null

// Check existence
if (isset($data['name'])) {
    echo $data['name'];
}

// Set value
$data['email'] = 'john@example.com';

// Remove
unset($data['email']);
```

With null object:

```php
$data = optional(null);

echo $data['name']; // null
echo $data['anything']; // null

// Will not error even if $data is null
```

<a id="use-cases"></a>
## Use Cases

<a id="avoiding-null-pointer"></a>
### Avoiding Null Pointer

Use `Optional` to avoid repetitive null checks:

```php
// Before: many null checks
$user = User::find($id);
$address = null;
$city = null;

if ($user) {
    $profile = $user->profile();
    if ($profile) {
        $address = $profile->address;
        if ($address) {
            $city = $address->city;
        }
    }
}

// With Optional: cleaner
$city = optional(optional(optional(User::find($id))->profile())->address)->city;

// Or more readable:
$user = optional(User::find($id));
$profile = optional($user->profile());
$address = optional($profile->address);
$city = $address->city;
```

<a id="api-response"></a>
### API Response

Handling API responses that may have incomplete structures:

```php
$response = json_decode($api_response);
$optional = optional($response);

// Safely access nested properties
$user_name = $optional->data['user']['name'];
$user_email = $optional->data['user']['email'];
$user_avatar = $optional->data['user']['profile']['avatar'];

// All will return null if not present, not error
```

<a id="database-query"></a>
### Database Query

Handling query results that may be empty:

```php
// Retrieve user and access relation
$user = optional(User::find($id));
$latest_post = $user->posts()->first();
$post_title = optional($latest_post)->title;

// Or in one line
$post_title = optional(optional(User::find($id))->posts()->first())->title;
```

With method chaining:

```php
// Without Optional - potential error
$user = User::find($id);
$comments_count = $user->posts()->first()->comments()->count();

// With Optional - safe
$user = optional(User::find($id));
$post = optional($user->posts()->first());
$comments_count = $post->comments()->count() ?: 0;
```

<a id="helper-function"></a>
## Helper Function

Rakit provides the `optional()` helper function for convenience:

```php
// Create Optional instance
$optional = optional($value);

// With callback (if value is not null, execute callback)
$result = optional($value, function ($value) {
    return $value->some_method();
});

// If $value is null, callback is not executed and returns null
```

Example with callback:

```php
$user = User::find($id);

// Execute callback only if user is found
$name = optional($user, function ($user) {
    return strtoupper($user->name);
});

// $name will contain name in uppercase if user is found,
// or null if user is not found
```

Combined with other methods:

```php
// Get email or default value
$email = optional(User::find($id))->email ?: 'no-email@example.com';

// Using ternary
$status = optional($user)->is_active ? 'Active' : 'Inactive';

// In condition
if (optional($user)->is_admin) {
    // Do something if user is admin
}
```

**Note:** Although `Optional` is very useful, use it wisely. Sometimes, it's better to handle `null` cases explicitly for easier debugging.
