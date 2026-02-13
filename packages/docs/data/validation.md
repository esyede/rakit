# Data Validation

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Validation Rules](#validation-rules)
    -   [Required](#required)
    -   [Alphabets, Numbers & Dashes](#alphabets-numbers--dashes)
    -   [Size](#size)
    -   [Numbers](#numbers)
    -   [Comparison](#comparison)
    -   [Inclusion & Exclusion](#inclusion--exclusion)
    -   [Confirmation](#confirmation)
    -   [Approval](#approval)
    -   [Same & Different](#same--different)
    -   [String & Format](#string--format)
    -   [Regular Expression](#regular-expression)
    -   [Uniqueness & Existence](#uniqueness--existence)
    -   [Date](#date)
    -   [E-Mail](#e-mail)
    -   [URL](#url)
    -   [IP Address](#ip-address)
    -   [File Upload](#file-upload)
    -   [Array](#array)
-   [Retrieving Error Messages](#retrieving-error-messages)
-   [Validation Guide](#validation-guide)
-   [Custom Error Messages](#custom-error-messages)
-   [Custom Validation Rules](#custom-validation-rules)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Almost every interactive web application needs to validate data. For example, a registration form
might require a password to be confirmed. Maybe the email address must be unique.

Validating data can be an impractical process. Fortunately, this is not the case with Rakit.
The `Validator` component provides an amazing number of validation helpers to make
the process of validating your data easy. Let's look at an example:

#### Get the array of input data to be validated:

```php
$input = Input::all();
```

#### Define validation rules for each data:

```php
$rules = [
    'name'  => 'required|max:50',
    'email' => 'required|email|unique:users',
];
```

In addition to using the `|` (pipe) character as a separator, you can also write it
in array syntax:

```php
$rules = [
    'name'  => ['required', 'max:50'],
    'email' => ['required', 'email', 'unique:users'],
];
```

#### Create a `Validator` instance and validate the data:

```php
$validation = Validator::make($input, $rules);

if ($validation->fails()) {
    return Redirect::back()->with_input()->with_errors($validation);
}

// Validation successful, proceed
```

Of course, default error messages have been included for all validation rules.
These default error messages are stored in the file `application/language/en/validation.php`.

**Complete example in controller:**

```php
class User_Controller extends Controller
{
    public function action_register()
    {
        $input = Input::all();

        $rules = [
            'name'     => 'required|max:50',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ];

        $validation = Validator::make($input, $rules);

        if ($validation->fails()) {
            return Redirect::back()
                ->with_input()
                ->with_errors($validation);
        }

        // Save new user
        $user = new User();
        $user->name = Input::get('name');
        $user->email = Input::get('email');
        $user->password = Hash::make(Input::get('password'));
        $user->save();

        return Redirect::to('login')
            ->with('message', 'Registration successful!');
    }
}
```

Now you are familiar with the basic usage of the `Validator` class. It's time to delve deeper
into what rules you can use to validate your data!
```

#### Define validation rules for each data:

```php
$rules = [
    'name'  => 'required|max:50',
    'email' => 'required|email|unique:users',
];
```

In addition to using the `|` (pipe) character as a separator, you can also write it
in array syntax:

```php
$rules = [
    'name'  => ['required', 'max:50'],
    'email' => ['required', 'email', 'unique:users'],
];
```

#### Create a `Validator` instance and validate the data:

```php
$validation = Validator::make($input, $rules);

if ($validation->fails()) {
    return Redirect::back()->with_input()->with_errors($validation);
}

// Validation successful, proceed
```

Of course, default error messages have been included for all validation rules.
These default error messages are stored in the file `application/language/en/validation.php`.

**Complete example in controller:**

```php
class User_Controller extends Controller
{
    public function action_register()
    {
        $input = Input::all();

        $rules = [
            'name'     => 'required|max:50',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ];

        $validation = Validator::make($input, $rules);

        if ($validation->fails()) {
            return Redirect::back()
                ->with_input()
                ->with_errors($validation);
        }

        // Save new user
        $user = new User();
        $user->name = Input::get('name');
        $user->email = Input::get('email');
        $user->password = Hash::make(Input::get('password'));
        $user->save();

        return Redirect::to('login')
            ->with('message', 'Registration successful!');
    }
}
```

Now you are familiar with the basic usage of the `Validator` class. It's time to delve deeper
into what rules you can use to validate your data!

<a id="validation-rules"></a>

## Validation Rules

-   [Required](#required)
-   [Alphabets, Numbers & Dashes](#alphabets-numbers--dashes)
-   [Size](#size)
-   [Numbers](#numbers)
-   [Comparison](#comparison)
-   [Inclusion & Exclusion](#inclusion--exclusion)
-   [Confirmation](#confirmation)
-   [Approval](#approval)
-   [Same & Different](#same--different)
-   [String & Format](#string--format)
-   [Regular Expression](#regular-expression)
-   [Uniqueness & Existence](#uniqueness--existence)
-   [Date](#date)
-   [E-Mail](#e-mail)
-   [URL](#url)
-   [IP Address](#ip-address)
-   [File Upload](#file-upload)
-   [Array](#array)

<a id="required"></a>

### Required

#### Validate that the attribute must exist and not be an empty string:

```php
'name' => 'required',
```

#### Validate that the attribute must exist if another attribute exists:

```php
'last_name' => 'required_with:first_name',
```

#### Validate that the attribute must exist if all other attributes exist:

```php
'address' => 'required_with_all:city,state,zip',
```

#### Validate that the attribute must exist if another attribute does not exist:

```php
'email' => 'required_without:phone',
```

#### Validate that the attribute must exist if all other attributes do not exist:

```php
'contact' => 'required_without_all:email,phone,address',
```

#### Validate that the attribute must exist if another attribute has a certain value:

```php
'tax_id' => 'required_if:country,US',
```

#### Validate that the attribute must exist unless another attribute has a certain value:

```php
'reason' => 'required_unless:status,approved',
```

#### Validate that the attribute key exists (even if its value is empty):

```php
'optional_field' => 'present',
```

The `present` rule ensures that the key exists in the input data, even if its value is `null` or an empty string.

**Example:**
```php
// Valid
['optional_field' => '']
['optional_field' => null]
['optional_field' => 'value']

// Invalid
// Key 'optional_field' does not exist in the array
```

#### Validate that the attribute exists and is not empty:

```php
'bio' => 'filled',
```

The `filled` rule ensures that if the field exists, it must have a value (cannot be empty).

**Difference between `required`, `present`, and `filled`:**

```php
// required: Field MUST exist and MUST contain a value
'name' => 'required',

// present: Field MUST exist, can be empty
'middle_name' => 'present',

// filled: Field can not exist, but if it exists MUST contain a value
'nickname' => 'filled',
```

**Practical example:**
```php
$rules = [
    'name' => 'required|string',           // Must exist and contain value
    'middle_name' => 'present|string',     // Must exist, can be empty
    'nickname' => 'filled|string',         // Optional, but if exists must contain value
];

// Valid data:
[
    'name' => 'John Doe',
    'middle_name' => '',        // OK because present, can be empty
    'nickname' => 'JD',         // OK because filled and contains value
]

[
    'name' => 'Jane Smith',
    'middle_name' => null,      // OK because present
    // 'nickname' not present       // OK because filled is optional
]

// Invalid data:
[
    'name' => 'Bob',
    // 'middle_name' not present    // NOT OK because present
    'nickname' => '',           // NOT OK because filled, must contain value if present
]
```

<a id="alphabets-numbers--dashes"></a>

### Alphabets, Numbers & Dashes

#### Validate that the attribute consists only of alphabetic letters:

```php
'name' => 'alpha',
```

#### Validate that the attribute consists only of alphabetic letters and numbers:

```php
'username' => 'alpha_num',
```

#### Validate that the attribute consists only of alphabetic letters, numbers, dashes and underscores:

```php
'username' => 'alpha_dash',
```

<a id="size"></a>

### Size

#### Validate that the attribute has a certain length, or, if the attribute is a number, it is a certain value:

```php
'name' => 'size:10',
```

#### Validate that the size of the attribute is within a certain range:

```php
'payment' => 'between:10,50',
```

> The minimum and maximum values are inclusive. That means, if the user inputs `10` or `50`
> then the `between()` validation above will pass.

#### Validate that the attribute must have at least a specified size:

```php
'payment' => 'min:10',
```

#### Validate that the size of the attribute does not exceed the specified:

```php
'payment' => 'max:50',
```

<a id="numbers"></a>

### Numbers

#### Validate that the attribute is a number:

```php
'payment' => 'numeric',
```

#### Validate that the attribute is an integer:

```php
'payment' => 'integer',
```

#### Validate that the attribute is a boolean:

```php
'is_active' => 'boolean',
```

This rule will accept: `true`, `false`, `1`, `0`, `"1"`, `"0"`.

<a id="comparison"></a>

### Comparison

#### Validate that the attribute is greater than (greater than) another attribute:

```php
'end_date' => 'after:start_date',
```

#### Validate that the date is equal to another date:

```php
'scheduled_date' => 'date_equals:2024-12-31',
'delivery_date' => 'date_equals:order_date',
```

**Example:**
```php
$rules = [
    'event_date' => 'date_equals:2024-01-15',
    'confirm_date' => 'date_equals:event_date',
];
```

<a id="e-mail"></a>
#### Validate that the attribute is greater than or equal to (greater than or equal) another attribute:

```php
'quantity' => 'gte:min_quantity',
```

#### Validate that the attribute is less than (less than) another attribute:

```php
'discount' => 'lt:price',
```

#### Validate that the attribute is less than or equal to (less than or equal) another attribute:

```php
'age' => 'lte:max_age',
```

#### Validate that the attribute has a certain number of digits:

```php
'pin' => 'digits:6',  // Must be 6 digits
```

#### Validate that the digits of the attribute are within a certain range:

```php
'otp' => 'digits_between:4,8',  // Between 4-8 digits
```

<a id="inclusion--exclusion"></a>

### Inclusion & Exclusion

#### Validate that the attribute is in a certain list:

```php
'size' => 'in:small,medium,large',
```

#### Validate that the attribute is not in a certain list:

```php
'language' => 'not_in:cobol,assembler',
```

<a id="confirmation"></a>

### Confirmation

The `'confirmed'` rule validates that, for a certain attribute, there must be another attribute
named `'xxx_confirmation'`, where `'xxx'` is the name of the original attribute.

#### Validate that the attribute has been confirmed:

```php
'password' => 'confirmed',
```

In the example above, the validator will validate that the value of the `'password'`
attribute matches the value in the `'password_confirmation'` attribute.

<a id="approval"></a>

### Approval

The `'accepted'` rule validates that the value of an attribute is one of:
`'yes'`, `'on'`, `'1'`, `1`, `true`, or `'true'`. This rule is very useful when
validating form checkboxes, such as a site rules approval checkbox.

#### Validate that the attribute has been approved:

```php
'terms' => 'accepted',
```

<a id="same--different"></a>

### Same & Different

#### Validate that the value of an attribute matches that of another attribute:

```php
'token1' => 'same:token2',
```

#### Validate that the values of two attributes have different values:

```php
'password' => 'different:old_password',
```

<a id="string--format"></a>

### String & Format

#### Validate that the attribute is a string:

```php
'name' => 'string',
```

#### Validate that the attribute is valid JSON:

```php
'metadata' => 'json',
```

#### Validate that the attribute is a valid timezone:

```php
'timezone' => 'timezone',
```

Valid timezone examples: `Asia/Jakarta`, `America/New_York`, `UTC`.

#### Validate that the attribute starts with a certain string:

```php
'phone' => 'starts_with:+62,08',
```

#### Validate that the attribute ends with a certain string:

```php
'domain' => 'ends_with:.com,.net,.org',
```

#### Validate that the attribute is in another array:

```php
'color' => 'in_array:colors.*',
```

Example: if `colors` is `['red', 'green', 'blue']`, then `color` must be one of those values.

<a id="regular-expression"></a>

### Regular Expression

The `'match'` rule validates that the value of an attribute matches a certain regular expression pattern.

#### Validate that the value of an attribute matches a certain regular expression pattern:

```php
'username' => 'match:/[a-zA-Z0-9]+/',
```

#### Validate that the value of an attribute does NOT match a regular expression pattern:

```php
'username' => 'not_regex:/[^a-zA-Z0-9]/',  // Must not contain special characters
```

When you use the `'match'` rule in a complex way, it is highly recommended to use
array syntax to avoid errors in the regex:

```php
$rules = [
    'username' => ['required', 'max:20', 'match:/[a-zA-Z0-9]+/'],
];
```

<a id="uniqueness--existence"></a>

### Uniqueness & Existence

#### Validate that the attribute is unique in a certain database table:

```php
'email' => 'unique:users',
```

In the example above, the `'email'` attribute will be checked for uniqueness in the `'users'` table.
Unique here means no duplicates, or matching data.

Need to check uniqueness on a different column name than the attribute name? No problem:

#### Specify a custom column name to check uniqueness:

```php
'email' => 'unique:users,email_address',
```

Often, when updating a record in the database, you want to use the `'unique'` rule,
but exclude the row being updated. For example, when updating a user's profile, you can
allow them to change their email address.

However, when the `'unique'` rule runs, it would certainly not apply to that specific user
because the user might not have changed their address, thus causing the unique rule to fail.

So how to overcome this? Easy:

#### Force the `unique` rule to ignore a certain ID:

```php
'email' => 'unique:users,email_address,10',
```

#### Validate that the attribute exists in a certain database table:

```php
'city' => 'exists:cities',
```

#### Specify a custom column name for the `exists` rule:

```php
'city' => 'exists:cities,abbreviation',
```

<a id="date"></a>

### Date

#### Validate that the date attribute is before a certain date:

```php
'birthdate' => 'before:1992-11-02',
```

#### Validate that the date attribute is after a certain date:

```php
'birthdate' => 'after:1992-11-02',
```

> The `before` and `after` rules use the
> [strtotime()](https://php.net/manual/en/function.strtotime.php) function to
> convert the given string date.

#### Validate that the date attribute format matches a certain format:

```php
'start_date' => 'date_format:H\\:i',
```

> In the example above, `\\` (double backslash) is used to escape `:` (colon) so that the character is not
> considered a parameter separator by PHP.

Date formatting options can be read in
[PHP Date](https://php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters).

<a id="e-mail"></a>

### E-Mail

#### Validate that the attribute is an email address:

```php
'address' => 'email',
```

> This rule uses the [filter_var()](https://php.net/manual/en/function.filter-var.php)
> function for checking.

<a id="url"></a>

### URL

#### Validate that the attribute is a URL:

```php
'link' => 'url',
```

#### Validate that the attribute is an active URL:

```php
'link' => 'active_url',
```

> This rule uses the [checkdnsrr()](https://php.net/manual/en/function.checkdnsrr.php)
> function for checking.

<a id="ip-address"></a>

### IP Address

#### Validate that the attribute is a valid IP address:

```php
'server_ip' => 'ip',
```

This rule will validate both IPv4 and IPv6.

#### Validate that the attribute is an IPv4 address:

```php
'server_ip' => 'ipv4',
```

**Valid IPv4 examples:**
- `192.168.1.1`
- `10.0.0.1`
- `127.0.0.1`

#### Validate that the attribute is an IPv6 address:

```php
'server_ip' => 'ipv6',
```

**Valid IPv6 examples:**
- `2001:0db8:85a3:0000:0000:8a2e:0370:7334`
- `2001:db8:85a3::8a2e:370:7334`
- `::1`
- `fe80::1`

**Usage example:**

```php
$rules = [
    'ipv4_address' => 'required|ipv4',
    'ipv6_address' => 'required|ipv6',
    'any_ip' => 'required|ip',
];

$validation = Validator::make($input, $rules);
```

<a id="file-upload"></a>

### File Upload

The `mimes` rule validates that the uploaded file has a certain MIME type.
This rule uses the [PHP Fileinfo](http://php.net/manual/en/book.fileinfo.php) extension to
read the file content and determine the actual MIME type.

#### Validate that the file is one of the given mime-types:

```php
'picture' => 'mimes:jpg,png,gif',
```

> When validating files, make sure to use `Input::file()` or `Input::all()`
> to retrieve input data from the user.

#### Validate MIME type based on file extension:

```php
'document' => 'mimetypes:application/pdf,application/msword',
```

**Difference between `mimes` and `mimetypes`:**
- `mimes`: Validates based on file extension (jpg, png, pdf)
- `mimetypes`: Validates based on actual MIME type (image/jpeg, application/pdf)

**Example:**
```php
// Mimes (by extension)
'photo' => 'mimes:jpg,jpeg,png,gif',

// Mimetypes (by actual MIME type)
'photo' => 'mimetypes:image/jpeg,image/png,image/gif',
```

#### Validate that the file is an image:

```php
'picture' => 'image',
```

#### Validate image dimensions:

```php
'avatar' => 'dimensions:min_width=100,min_height=100',
'banner' => 'dimensions:width=1200,height=300',
'photo' => 'dimensions:max_width=2000,max_height=2000',
'thumbnail' => 'dimensions:ratio=16/9',
```

**Available constraints for dimensions:**
- `min_width`: Minimum width
- `max_width`: Maximum width
- `min_height`: Minimum height
- `max_height`: Maximum height
- `width`: Exact width
- `height`: Exact height
- `ratio`: Aspect ratio (example: 16/9, 4/3, 1/1)

**Combination example:**
```php
'avatar' => 'image|dimensions:min_width=100,min_height=100,max_width=500,max_height=500',
```

#### Validate that the file size does not exceed the specified size in kilobytes:

```php
'picture' => 'image|max:2048',  // Max 2MB
```

**Complete file upload validation example:**

```php
$input = Input::all();

$rules = [
    'avatar' => 'required|file|image|mimes:jpg,png|max:2048|dimensions:min_width=100,min_height=100',
    'document' => 'required|file|mimetypes:application/pdf|max:5120',
    'photo' => 'image|dimensions:ratio=16/9',
];

$validation = Validator::make($input, $rules);

if ($validation->fails()) {
    return Redirect::back()
        ->with_input()
        ->with_errors($validation);
}

// Upload file
$avatar = Input::file('avatar');
$avatar->move(path('storage') . 'uploads');
```

<a id="array"></a>

### Array

#### Validate that the attribute is an array:

```php
'tags' => 'array',
```

#### Validate that all values in the array are unique:

```php
'emails' => 'distinct',
```

Example:
```php
// Valid
['email@example.com', 'other@example.com']

// Invalid (has duplicate)
['email@example.com', 'email@example.com']
```

#### Validate that the attribute is an array, and has exactly 3 elements:

```php
'categories' => 'array|count:3',
```

#### Validate that the attribute is an array, and has 1 to 3 elements:

```php
'categories' => 'array|countbetween:1,3',
```

#### Validate that the attribute is an array, and has at least 2 elements:

```php
'categories' => 'array|countmin:2',
```

#### Validate that the attribute is an array, and has a maximum of 2 elements:

```php
'categories' => 'array|countmax:2',
```

<a id="retrieving-error-messages"></a>

## Retrieving Error Messages

Handling error messages has become very easy thanks to Rakit's simple error collector class.
After calling the `passes()` or `fails()` method of the `Validator` class, you can access
the error messages via the `$errors` property.

The error collector class has several helper methods to make it easier for you to
retrieve error messages:

#### Check if a certain attribute has error messages:

```php
if ($validation->errors->has('email')) {
    // The email attribute has errors..
}
```

#### Get the first error message for an attribute:

```php
echo $validation->errors->first('email');
```

Sometimes you may need to format the error message by wrapping it in HTML tags.

No problem. Just pass the format you want along with the `:message` placeholder
to the second parameter.

#### Format an error message:

```php
echo $validation->errors->first('email', '<p>:message</p>');
```

#### Get all error messages for a specific attribute:

```php
$messages = $validation->errors->get('email');
```

#### Format all error messages for a specific attribute:

```php
$messages = $validation->errors->get('email', '<p>:message</p>');
```

#### Get all error messages for all attributes:

```php
$messages = $validation->errors->all();
```

#### Format all error messages for all attributes:

```php
$messages = $validation->errors->all('<p>:message</p>');
```

<a id="validation-guide"></a>

## Validation Guide

After performing validation, you need an easy way to return those error messages
to the view so they can be seen by the user.

Easy. Let's explore the common scenario below. We will define two routes:

```php
Route::get('register', function () {
    return View::make('register');
});

Route::post('register', function () {
    $rules = [ ... ]; // validation rules here

    $validation = Validator::make(Input::all(), $rules);

    if ($validation->fails()) {
        return Redirect::to('register')->with_errors($validation);
    }
});
```

Awesome! So, we have two routes for account registration. One to display
the form view, and one to handle the POST data sent from that form.

In the POST route, we run some validation on the user input. If validation fails,
we redirect back to the form and flash the validation error messages to the session
so they can be accessed globally, thus we can display the error messages in the view.

**Note that we do not explicitly bind the error messages to the view in our GET route**.

However, the `$error` variable will still be available in the view. Rakit cleverly determines if
there are errors in the session, and if there are, it automatically binds them to the view for you.

If there are no errors in the session, an empty message container will still be bound to the view.

In your view, this allows you to always assume you have a message container
available through the `$error` variable. This will definitely make your life easier.

For example, if email validation fails, we can look for `'email'` in the `$error` session.

```php
$errors->has('email')
```

With [Blade](/docs/views/templating#blade-template-engine), we can then add
the error message to our view conditionally:

```php
@if ($errors->has('email'))
    <span class="error">{{ $errors->first('email') }}</span>
@endif
```

This will also work well when we need to add classes conditionally when using
something like Bootstrap. For example, if email validation fails, we might want
to add the `"error"` class from Bootstrap to the `<div class="control-group">`.

```html
<div class="control-group{{ $errors->has('email') ? ' error' : '' }}"></div>
```

When validation fails, the view we render will have the `'error'` class added.

```html
<div class="control-group error"></div>
```

<a id="custom-error-messages"></a>

## Custom Error Messages

Want to use error messages other than the defaults? Maybe you even want to use custom error messages
for specific attributes and rules. Sure!

#### Create an array of custom error messages for the Validator:

```php
$messages = [
    'required' => 'The :attribute field is required.',
    'email'    => ':attribute must be a valid email address.',
    'unique'   => ':attribute is already registered.',
];

$validation = Validator::make($input, $rules, $messages);

if ($validation->fails()) {
    return Redirect::back()->with_input()->with_errors($validation);
}
```

**Practical example with form:**

```php
// Controller
public function action_store()
{
    $rules = [
        'name'  => 'required|max:50',
        'email' => 'required|email|unique:users',
    ];

    $messages = [
        'name.required'  => 'Name is required.',
        'name.max'       => 'Name maximum 50 characters.',
        'email.required' => 'Email is required.',
        'email.email'    => 'Invalid email format.',
        'email.unique'   => 'Email is already registered.',
    ];

    $validation = Validator::make(Input::all(), $rules, $messages);

    if ($validation->fails()) {
        return Redirect::back()
            ->with_input()
            ->with_errors($validation);
    }

    // Save data
}
```

**In view:**

```php
<form method="POST" action="<?php echo URL::to('users'); ?>">
    <div class="form-group<?php echo $errors->has('name') ? ' has-error' : ''; ?>">
        <label>Name</label>
        <input type="text" name="name" value="<?php echo Input::old('name'); ?>">
        <?php if ($errors->has('name')): ?>
            <span class="help-block"><?php echo $errors->first('name'); ?></span>
        <?php endif; ?>
    </div>

    <div class="form-group<?php echo $errors->has('email') ? ' has-error' : ''; ?>">
        <label>Email</label>
        <input type="email" name="email" value="<?php echo Input::old('email'); ?>">
        <?php if ($errors->has('email')): ?>
            <span class="help-block"><?php echo $errors->first('email'); ?></span>
        <?php endif; ?>
    </div>

    <button type="submit">Submit</button>
</form>
```

Now your custom messages will be used whenever the required validation check fails.
But, what is the `:attribute` placeholder? Why does it suddenly appear there?

To make your life easier, the `Validator` class will replace the `:attribute` placeholder
with the actual attribute name!

The validator class will also remove underscores from the attribute name so it looks nicer for the user.

You can also use placeholders `:other`, `:size`, `:min`, `:max`, and `:values` when
creating custom error messages:

#### Other placeholders for validation error messages:

```php
$messages = [
    'same'    => 'The :attribute and :other fields must be the same.',
    'size'    => 'The :attribute field must be of size :size.',
    'between' => 'The :attribute field must be between :min - :max.',
    'in'      => 'The :attribute field must contain one of: :values',
];
```

Then, if you need to specify a custom message, but only for the _email_ attribute?
Easy. Just specify the message using the naming convention `[attribute name]` + `_` + `[rule name]`
like this:

#### Specify custom error messages for a specific attribute:

```php
$messages = [
    'email_required' => 'We need your email address!',
    'email_email'    => 'Invalid email format!',
];

$validation = Validator::make($input, $rules, $messages);
```

In the example above, the required custom message will be used for the email attribute, while
default messages will be used for all other attributes.

However, if you use many custom error messages, writing them inline in every
validation code will certainly make your code messy and cluttered.

Therefore, you can put your custom messages in the `'custom'` array in
the validation language file:

#### Add custom error messages via validation language file:

**File: `application/language/en/validation.php`**

```php
'custom' => [
    'email_required' => 'We need your email address!',
    'email_email'    => 'Invalid email format!',
    'name_required'  => 'Name is required!',
    'name_max'       => 'Name is too long!',
],
```

With this way, error messages are centralized and easy to manage.

<a id="custom-validation-rules"></a>

## Custom Validation Rules

Rakit has provided a number of validation rules that are commonly used by many people.
However, it is very possible that you need to create custom validation rules for your own needs.

There are two simple methods to create custom validation rules. Both are solid, so use whichever
suits your needs best.

#### Register a custom validation rule:

```php
Validator::register('humble', function ($attribute, $value, $params) {
    return ($value === 'humble');
});
```

In this example, we register a new validation rule to the validator. The rule receives
three parameters. The first is the name of the attribute to be validated,
the second is the value of the attribute to be validated, and the third is an array of parameters
specified for that rule.

Here is how your custom validation rule is called:

```php
$rules = [
    'attitude' => 'required|humble',
];
```

Of course, you need to define an error message for your new rule. You can do this
either in an array like this:

```php
$messages = [
    'humble' => 'You must always be humble!',
];

$validator = Validator::make(Input::get(), $rules, $messages);
```

Or by adding it to the `language/en/validation.php` file:

```php
'humble' => 'You must always be humble!',
```

As mentioned above, you can specify and receive an array of parameters in your custom rule:

```php
// Register custom rule
Validator::register('humble', function ($attribute, $value, $params) {
    return ($value === 'yes');
});

// Usage
$rules = [
    'attitude' => 'required|humble:yes',
];
```

In this case, the parameter array of your validation rule will receive one element: `'yes'`.

The other method for creating and storing custom validation rules is by extending
the `Validator` class itself. By extending the class, you create a new version of the validator
that has all the existing functionality combined with your own custom additions.

You can even choose to override some default methods if you want.
Let's see an example!

First, create a class that extends `Validator` and place it in
your `application/libraries/` directory:

#### Create a custom validator class:

```php
class Validator extends \System\Validator
{
    // ..
}
```

Next, remove `Validator` from the aliases array in the `config/aliases.php` file. This is
necessary because there would be two classes named Validator otherwise, which would conflict:

```php
'aliases' => [
    // ..

    'Validator' => 'System\Validator', // Remove this part

    // ..
],
```

Next, just move our `'humble'` rule into that class:

#### Add custom validation rules to the class:

```php
class Validator extends \System\Validator
{
    public function validate_humble($attribute, $value, $params)
    {
        return ($value === 'yes');
    }
}
```

Note that the method name is named using the convention `validate_` + `[rule name]`.
Our rule is named `'humble'` so the method must be named `validate_humble`.

All validation methods in the Validator class must return `TRUE` or `FALSE`, nothing else.

Remember that you still need to create a custom error message for the new validation rule you created.
