# Data Validation

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Validating Input](#validating-input)
    -   [Optional Fields](#optional-fields)
    -   [Validating Arrays](#validating-arrays)
    -   [Stopping Early](#stopping-early)
    -   [Excluding Fields](#excluding-fields)
-   [Validated Data](#validated-data)
-   [Displaying Errors](#displaying-errors)
-   [Retrieving Error Messages](#retrieving-error-messages)
-   [Available Rules](#available-rules)
    -   [Required](#required)
    -   [Alphabets, Numbers & Dashes](#alphabets-numbers--dashes)
    -   [Size](#size)
    -   [Numbers](#numbers)
    -   [Comparison](#comparison)
    -   [Inclusion & Exclusion](#inclusion--exclusion)
    -   [Confirmation & Approval](#confirmation--approval)
    -   [Same & Different](#same--different)
    -   [String & Format](#string--format)
    -   [Regular Expression](#regular-expression)
    -   [Uniqueness & Existence](#uniqueness--existence)
    -   [Date](#date)
    -   [E-Mail, URL & IP](#e-mail-url--ip)
    -   [Files](#files)
    -   [Array](#array)
    -   [Other Rules](#other-rules)
-   [Custom Error Messages](#custom-error-messages)
-   [Custom Validation Rules](#custom-validation-rules)
    -   [Closure Rules](#closure-rules)
-   [Validator Options](#validator-options)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

`Validator` checks an array of data (usually `Input::all()`) against a set of rules and
collects an error message for every failure. Default messages live in
`application/language/en/validation.php`.

<a id="validating-input"></a>

## Validating Input

Pass the data and the rules to `Validator::make()`, then call `fails()` (or `passes()`):

```php
$validation = Validator::make(Input::all(), [
    'name'     => 'required|max:50',
    'email'    => 'required|email|unique:users',
    'password' => 'required|min:6|confirmed',
]);

if ($validation->fails()) {
    return Redirect::back()->with_input()->with_errors($validation);
}
```

`valid()` and `invalid()` are aliases of `passes()` and `fails()`. Rules may also be given as
an array, which is required when a parameter contains a `|` (such as a regex):

```php
'name' => ['required', 'max:50'],
```

> A rule that needs a parameter throws an `InvalidArgumentException` when written without one:
> write `'min:18'`, not `'min'`.

<a id="optional-fields"></a>

### Optional Fields

When a value is empty (`null`, a blank string, an empty array or a file input with no upload),
only the "implicit" rules run on it: `required`, `required_*`, `present`, `filled`, `accepted`
and `missing`. Every other rule is skipped, so `'website' => 'url'` accepts an empty field.

`nullable` marks `null` (or an absent key) as acceptable: the non-implicit rules are skipped,
but the implicit ones above still run, as in Laravel. So `'required|nullable'` still fails on
`null`, while `'nullable|max:20'` accepts it.

```php
'nickname' => 'nullable|max:20',       // null passes
'email'    => 'required|nullable|email', // null fails: required still runs
```

`sometimes` goes further: when the key is absent from the data, none of the attribute's rules
run, `required` included. When it is present, every rule runs as usual. It suits updates that
send only the fields that changed:

```php
'email' => 'sometimes|required|email', // absent passes, '' fails
```

<a id="validating-arrays"></a>

### Validating Arrays

Use `*` for "every element". Errors are recorded against the element that failed (`tags.1`),
and a custom message written for `tags.*` applies to every element.

```php
'tags'          => 'required|array',
'tags.*'        => 'integer',
'people.*.name' => 'required|max:50',
```

<a id="stopping-early"></a>

### Stopping Early

`bail` stops an attribute at its first failing rule, so it gets one message, and an expensive
rule after it (such as `unique`) does not run. Other attributes are still validated:

```php
'email' => 'bail|required|email|unique:users',
```

To stop the whole validation at the first attribute that fails, call `stop_on_first_failure()`:

```php
Validator::make($input, $rules)->stop_on_first_failure()->fails();
```

<a id="excluding-fields"></a>

### Excluding Fields

An excluded attribute is neither validated nor returned by [`validated()`](#validated-data):

| Rule | Excludes the attribute |
| --- | --- |
| `exclude` | always |
| `exclude_if:field,value,...` | when `field` equals one of the values |
| `exclude_unless:field,value,...` | unless `field` equals one of the values |
| `exclude_with:field` | when `field` is present and not empty |
| `exclude_without:field` | when `field` is absent or empty |

```php
'type'    => 'required|in:personal,business',
'company' => 'exclude_if:type,personal|required|max:100',
```

<a id="validated-data"></a>

## Validated Data

`validated()` returns only the attributes that have rules, are present in the data, and were
not excluded, keeping their nesting. Keys nobody wrote a rule for, such as an `is_admin` added to
the form, are left out, which makes it the safe input for `create()` and `fill()`:

```php
$validation = Validator::make(Input::all(), [
    'name'          => 'required|max:50',
    'address.city'  => 'required',
    'tags.*.id'     => 'integer',
]);

if ($validation->fails()) {
    return Redirect::back()->with_input()->with_errors($validation);
}

User::create($validation->validated());
// ['name' => .., 'address' => ['city' => ..], 'tags' => [['id' => ..], ..]]
```

A rule on the parent (`'address' => 'array'`) returns the whole parent, every key in it.
`validated()` validates first if needed, and throws a `RuntimeException` when the data is invalid,
so invalid input is never handed on by accident.

`safe()` returns the same data as a [Collection](/docs/collection):

```php
$validation->safe()->only(['name', 'email'])->all();
$validation->safe()->except(['password'])->all();
```

<a id="displaying-errors"></a>
<a id="validation-guide"></a>

## Displaying Errors

Every view receives an `$errors` [Messages](/docs/messages) bag: the one flashed by
`->with_errors($validation)`, or an empty one, so you never need to pass it yourself.

```php
Route::get('register', function () {
    return View::make('register');
});

Route::post('register', function () {
    $validation = Validator::make(Input::all(), $rules);

    if ($validation->fails()) {
        return Redirect::to('register')->with_input()->with_errors($validation);
    }

    // ...
});
```

```blade
<div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
    <input type="email" name="email" value="{{ Input::old('email') }}">

    @if ($errors->has('email'))
        <span class="error">{{ $errors->first('email') }}</span>
    @endif
</div>
```

<a id="retrieving-error-messages"></a>

## Retrieving Error Messages

After `passes()`/`fails()`, the errors are in `$validation->errors`. Every getter takes an
optional format with a `:message` placeholder:

```php
$validation->errors->has('email');
$validation->errors->first('email');
$validation->errors->first('email', '<p>:message</p>');
$validation->errors->get('email');                    // all messages for one attribute
$validation->errors->get('email', '<p>:message</p>');
$validation->errors->all();                           // all messages for all attributes
$validation->errors->all('<p>:message</p>');
```

<a id="available-rules"></a>
<a id="validation-rules"></a>

## Available Rules

<a id="required"></a>

### Required

| Rule | Passes when |
| --- | --- |
| `required` | the value is present and not empty (an empty string, whitespace-only string or empty array is empty; `0` and `'0'` are not) |
| `filled` | same as `required` (a missing field fails it too) |
| `present` | the key exists, even if its value is `null` or `''` |
| `nullable` | always; when the value is `null`, the non-implicit rules are skipped ([see above](#optional-fields)) |
| `required_with:a,b` | required if **any** of the listed fields is filled |
| `required_with_all:a,b` | required if **all** of the listed fields are filled |
| `required_without:a,b` | required if **any** of the listed fields is empty |
| `required_without_all:a,b` | required if **all** of the listed fields are empty |
| `required_if:field,v1,v2` | required if `field` equals one of the values |
| `required_unless:field,v1,v2` | required unless `field` equals one of the values |

```php
'last_name' => 'required_with:first_name',
'tax_id'    => 'required_if:country,US,CA',
'reason'    => 'required_unless:status,approved',
```

<a id="alphabets-numbers--dashes"></a>

### Alphabets, Numbers & Dashes

| Rule | Passes when |
| --- | --- |
| `alpha` | only letters (Unicode) |
| `alpha_num` | only letters and digits |
| `alpha_dash` | only letters, digits, `-` and `_` |

<a id="size"></a>

### Size

"Size" depends on the value: string length, the number itself (only when the attribute also has
`numeric` or `integer`), array element count, or file size in kilobytes.

| Rule | Passes when |
| --- | --- |
| `size:10` | size equals 10 |
| `between:10,50` | size is between 10 and 50, inclusive |
| `min:10` | size is at least 10 |
| `max:50` | size is at most 50 |

```php
'age'  => 'integer|min:18',   // the number
'name' => 'max:50',           // characters
'tags' => 'array|max:3',      // elements
'file' => 'max:2048',         // kilobytes
```

<a id="numbers"></a>

### Numbers

| Rule | Passes when |
| --- | --- |
| `numeric` | `is_numeric()` |
| `integer` | a valid integer |
| `boolean` | `true`, `false`, `1`, `0`, `'1'` or `'0'` |

<a id="comparison"></a>

### Comparison

`gt`, `gte`, `lt` and `lte` compare against **another attribute**, and fail when it is missing.

| Rule | Passes when |
| --- | --- |
| `gt:cost` | greater than the `cost` field |
| `gte:min_quantity` | greater than or equal to the field |
| `lt:price` | less than the field |
| `lte:max_age` | less than or equal to the field |
| `digits:6` | only digits, exactly 6 of them |
| `digits_between:4,8` | only digits, 4 to 8 of them |
| `date_equals:2024-12-31` | the same date as the given date (see [Date](#date)) |

<a id="inclusion--exclusion"></a>

### Inclusion & Exclusion

| Rule | Passes when |
| --- | --- |
| `in:small,medium,large` | the value is one of the list |
| `not_in:cobol,assembler` | the value is not in the list |

<a id="confirmation--approval"></a>
<a id="confirmation"></a>
<a id="approval"></a>

### Confirmation & Approval

| Rule | Passes when |
| --- | --- |
| `confirmed` | a matching `{field}_confirmation` field exists, e.g. `password_confirmation` |
| `accepted` | `'yes'`, `'on'`, `'1'`, `1`, `true` or `'true'` (what a ticked checkbox sends); a missing field fails |
| `declined` | `'no'`, `'off'`, `'0'`, `0`, `false` or `'false'` |

<a id="same--different"></a>

### Same & Different

| Rule | Passes when |
| --- | --- |
| `same:token2` | equals the other field (which must exist) |
| `different:old_password` | differs from the other field (which must exist) |

<a id="string--format"></a>

### String & Format

| Rule | Passes when |
| --- | --- |
| `string` | the value is a string |
| `json` | a valid JSON string |
| `uuid` | a valid UUID |
| `timezone` | a valid timezone identifier, e.g. `Asia/Jakarta`, `UTC` |
| `starts_with:+62,08` | starts with one of the values |
| `ends_with:.com,.net` | ends with one of the values |
| `in_array:colors.*` | is one of the values of the `colors` field (`in_array:colors` and nested paths like `form.colors` work too) |

<a id="regular-expression"></a>

### Regular Expression

| Rule | Passes when |
| --- | --- |
| `match:/pattern/` | the value matches the pattern (`regex:` is an alias) |
| `not_regex:/pattern/` | the value does not match |

Use the array syntax so a `|` in the pattern is not read as a rule separator:

```php
'username' => ['required', 'match:/^[a-z0-9]+$/i'],
```

<a id="uniqueness--existence"></a>

### Uniqueness & Existence

These rules query the default database connection (see [connection()](#validator-options)).

```php
'email' => 'unique:users',                       // no row in users with this email
'email' => 'unique:users,email_address',         // check a different column
'email' => 'unique:users,email_address,10',      // ignore the row whose id is 10 (for updates)
'email' => 'unique:users,email_address,10,uid',  // ...whose `uid` column is 10

'city'  => 'exists:cities',                      // a row in cities has this city
'city'  => 'exists:cities,abbreviation',         // check a different column
```

When the value of `exists` is an array, every element must exist. A value that is not a string or
number (an array for `unique`, nested arrays for `exists`) fails without querying, and a table or
column name that is not a plain identifier throws an `InvalidArgumentException`.

Never append an ID from the request to the rule string: `'unique:users,email,'.$id` with `$id`
set to `5,password` adds a parameter of its own. Build the rule with `Validator::unique_rule()`,
which keeps the ID one value, and use the array form of the rules:

```php
'email' => ['required', 'email', Validator::unique_rule('users', 'email', $user->id)],
```

<a id="date"></a>

### Date

Dates are parsed with PHP's [DateTime](https://www.php.net/manual/en/datetime.construct.php)
and compared against the given date string (not another attribute).

| Rule | Passes when |
| --- | --- |
| `date` | a valid date |
| `before:1992-11-02` | before the date |
| `before_or_equal:2024-12-31` | before or on the date (`before_or_equals` also works) |
| `after:1992-11-02` | after the date |
| `after_or_equal:2024-12-31` | after or on the date (`after_or_equals` also works) |
| `date_format:Y-m-d` | matches the [format](https://www.php.net/manual/en/datetime.createfromformat.php) exactly |

`date_format` is strict: the date must read back the way it came in, so `Y-m-d` accepts
`2026-01-01` but not `2026-1-1`. A format containing `:` works as-is (`date_format:H:i`), since
only the first `:` separates the rule from its parameter.

<a id="e-mail-url--ip"></a>
<a id="e-mail"></a>
<a id="url"></a>
<a id="ip-address"></a>

### E-Mail, URL & IP

| Rule | Passes when |
| --- | --- |
| `email` | a valid email address (`filter_var()`) |
| `url` | a valid URL |
| `active_url` | an http(s) URL whose host has a DNS `A` or `AAAA` record (no request is made) |
| `ip` | a valid IPv4 or IPv6 address |
| `ipv4` | a valid IPv4 address |
| `ipv6` | a valid IPv6 address |

<a id="files"></a>
<a id="file-upload"></a>
<a id="validating-files"></a>

### Files

File rules expect the `$_FILES` entry, so validate `Input::all()` (or `Input::file()`). File
types are detected from the content with [Fileinfo](https://www.php.net/manual/en/book.fileinfo.php),
not from the file name.

| Rule | Passes when |
| --- | --- |
| `file` | a successfully uploaded file |
| `mimes:jpg,png,pdf` | the detected type matches one of the extensions |
| `mimetypes:image/jpeg,application/pdf` | the detected MIME type is one of the list |
| `image` | jpeg, png, gif, bmp or webp |
| `image:allow_svg` | the above, or svg |
| `dimensions:...` | the image meets the constraints: `width`, `height`, `min_width`, `max_width`, `min_height`, `max_height` |
| `max:2048` | at most 2048 KB (see [Size](#size)) |

```php
'avatar'   => 'required|image|max:2048|dimensions:min_width=100,min_height=100',
'document' => 'required|mimetypes:application/pdf|max:5120',
```

> SVG is excluded from `image` by default because an SVG can carry script; serving one back from
> your domain is a stored XSS.

<a id="array"></a>

### Array

| Rule | Passes when |
| --- | --- |
| `array` | the value is an array |
| `array:name,email` | an array whose keys are all in the list |
| `list` | a sequential array (keys `0..n-1`) |
| `distinct` | the array holds no duplicate values |
| `contains:admin,editor` | the array holds every one of the values |
| `count:3` | an array with exactly 3 elements |
| `countmin:2` | an array with at least 2 elements |
| `countmax:2` | an array with at most 2 elements |
| `countbetween:1,3` | an array with 1 to 3 elements |

<a id="other-rules"></a>

### Other Rules

| Rule | Passes when |
| --- | --- |
| `lowercase` | entirely lowercase |
| `uppercase` | entirely uppercase |
| `ascii` | only ASCII characters |
| `decimal:2` | exactly 2 decimal places (`decimal:2,4` for a range) |
| `multiple_of:5` | a multiple of 5 |
| `min_digits:4` | a number with at least 4 digits |
| `max_digits:6` | a number with at most 6 digits |
| `doesnt_start_with:admin,root` | does not start with any of the values |
| `doesnt_end_with:.exe,.bat` | does not end with any of the values |
| `missing` | the key is not in the input at all |
| `prohibited` | the key may be there, but must be empty |
| `mac_address` | a valid MAC address |
| `ulid` | a valid ULID |
| `hex_color` | `#fff`, `#ffff`, `#ffffff` or `#ffffffff` |

<a id="custom-error-messages"></a>

## Custom Error Messages

Pass messages as the third argument to `make()`. Key them by rule to override it for every
attribute, or by `{attribute}_{rule}` for one attribute:

```php
$messages = [
    'required'       => 'The :attribute field is required.',
    'email_required' => 'We need your email address!',
    'between'        => 'The :attribute must be between :min and :max.',
];

$validation = Validator::make(Input::all(), $rules, $messages);
```

`:attribute` becomes the attribute name with underscores replaced by spaces. Rules also fill
their own placeholders, such as `:other`, `:size`, `:min`, `:max`, `:values`, `:value`,
`:date`, `:format`, `:digits` and `:count` (see the default messages for which rule uses which).

To keep messages out of your code, put `{attribute}_{rule}` messages in the `custom` array of
the language file, and friendly attribute names in its `attributes` array:

```php
// application/language/en/validation.php
'custom' => [
    'email_required' => 'We need your email address!',
],

'attributes' => [
    'dob' => 'date of birth',
],
```

A message is looked up in this order: inline `{attribute}_{rule}`, the language file's `custom`
entry, inline `{rule}`, then the default line. The defaults for `size`, `between`, `min` and
`max` have `numeric`, `file`, `array` and `string` variants, picked from the value.

<a id="custom-validation-rules"></a>

## Custom Validation Rules

Register a closure. It receives the attribute name, the value and the rule's parameters, and
returns `TRUE` when the value passes:

```php
Validator::register('humble', function ($attribute, $value, $parameters) {
    return $value === $parameters[0];
});

$rules = ['attitude' => 'required|humble:yes'];
```

Give the rule a message, either inline (`'humble' => 'You must be humble!'`) or as a `'humble'`
line in `application/language/en/validation.php`.

Alternatively, extend the validator. Create `application/libraries/validator.php` with a
`validate_{rule}` method, then remove the `'Validator' => 'System\Validator'` entry from
`application/config/aliases.php` so your class takes its place:

```php
class Validator extends \System\Validator
{
    public function validate_humble($attribute, $value, $parameters)
    {
        return $value === 'yes';
    }
}
```

Validation methods must return `TRUE` or `FALSE`, and you can override built-in ones the same way.

<a id="closure-rules"></a>

### Closure Rules

For a rule used in one place, put a closure in the rule array. It receives the attribute, the
value and a `$fail` callback; call `$fail()` with a message to fail it (`:attribute` is replaced):

```php
$validation = Validator::make(Input::all(), [
    'coupon' => ['required', 'string', function ($attribute, $value, $fail) {
        if (! Coupon::where('code', '=', $value)->where('active', '=', 1)->first()) {
            $fail('The :attribute is not valid.');
        }
    }],
]);
```

`$fail()` also takes a language key, or no message at all:

```php
$fail('validation.custom.coupon'); // a dotted key is translated in the validator's language
$fail();                           // the 'closure' line: "The :attribute is invalid."
```

With no message, a custom message for `<attribute>_closure` (inline or under `custom` in the
language file) is used before the `closure` line, as for any other rule.

Like every rule that is not implicit, a closure is skipped when the value is empty. Closures only
fit the array form of the rules, not a `|` separated string.

<a id="validator-options"></a>

## Validator Options

```php
Validator::make($input, $rules)
    ->speaks('id')                              // read messages in another language
    ->package('admin')                          // read messages from a package's language files
    ->connection(Database::connection('mysql')) // DB connection for unique/exists
    ->fails();
```
