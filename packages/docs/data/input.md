# Input & Cookies

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Retrieving Input](#retrieving-input)
  - [Get All Input](#get-all-input)
  - [Get Specific Input](#get-specific-input)
  - [Get with Default Value](#get-with-default-value)
  - [Only & Except](#only--except)
- [Input Presence](#input-presence)
  - [Check Input Exists](#check-input-exists)
  - [Check Input Filled](#check-input-filled)
  - [Check Input Empty](#check-input-empty)
- [Query String](#query-string)
- [JSON Input](#json-input)
- [File Upload](#file-upload)
  - [Get Uploaded File](#get-uploaded-file)
  - [Check File Exists](#check-file-exists)
  - [Upload File](#upload-file)
  - [File Information](#file-information)
  - [File Validation](#file-validation)
- [Old Input](#old-input)
  - [Flash Input](#flash-input)
  - [Retrieve Old Input](#retrieve-old-input)
  - [Flash with Filter](#flash-with-filter)
  - [Clear Old Input](#clear-old-input)
- [Redirect with Old Input](#redirect-with-old-input)
- [Input Manipulation](#input-manipulation)
  - [Merge Input](#merge-input)
  - [Replace Input](#replace-input)
  - [Clear Input](#clear-input)
- [Cookies](#cookies)
  - [Get Cookie](#get-cookie)
  - [Set Cookie](#set-cookie)
  - [Delete Cookie](#delete-cookie)
  - [Forever Cookie](#forever-cookie)
- [Practical Examples](#practical-examples)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The `Input` class provides an easy and consistent way to access input from HTTP requests. Input can come from various sources such as `$_GET`, `$_POST`, `$_FILES`, or JSON request body.

**Advantages of using Input:**
- Consistent API for all input types
- Automatic XSS protection
- Support for old input (flash data)
- JSON input support
- File upload handling
- Cookie management

<a id="retrieving-input"></a>
## Retrieving Input

<a id="get-all-input"></a>
### Get All Input

**Get all input from request:**

```php
// All input (GET, POST, FILES)
$input = Input::all();

// Only GET and POST (excluding FILES)
$input = Input::get();
```

**Example:**

```php
// Form submission
$data = Input::all();
User::create($data);

// Or more specific
$user = new User;
$user->name = Input::get('name');
$user->email = Input::get('email');
$user->save();
```

<a id="get-specific-input"></a>
### Get Specific Input

**Get input based on key:**

```php
$name = Input::get('name');
$email = Input::get('email');
$age = Input::get('age');
```

**Nested input:**

```php
// Input: user[name], user[email]
$userName = Input::get('user.name');
$userEmail = Input::get('user.email');

// Array input
$tags = Input::get('tags'); // Array
$firstTag = Input::get('tags.0');
```

<a id="get-with-default-value"></a>
### Get with Default Value

**Default value if input does not exist:**

```php
$name = Input::get('name', 'Anonymous');
$page = Input::get('page', 1);
$perPage = Input::get('per_page', 20);
```

**Default value with closure:**

```php
$name = Input::get('name', function () {
    return 'Guest User';
});

$userId = Input::get('user_id', function () {
    return Auth::user()->id;
});
```

<a id="only--except"></a>
### Only & Except

**Get only specific input:**

```php
// Single argument
$credentials = Input::only('email', 'password');

// Array argument
$credentials = Input::only(['email', 'password']);

// Result: ['email' => '...', 'password' => '...']
```

**Get all except specific input:**

```php
// Exclude _token and _method
$data = Input::except('_token', '_method');

// Array argument
$data = Input::except(['_token', '_method']);
```

**Practical example:**

```php
// Login
$credentials = Input::only('email', 'password');
if (Auth::attempt($credentials)) {
    return Redirect::to('dashboard');
}

// Update profile (exclude protected fields)
$data = Input::except('id', 'password', 'remember_token');
Auth::user()->update($data);
```

<a id="input-presence"></a>
## Input Presence

<a id="check-input-exists"></a>
### Check Input Exists

**Check if input exists and is not empty:**

```php
if (Input::has('email')) {
    $email = Input::get('email');
}

// Multiple check
if (Input::has('name') && Input::has('email')) {
    // Both exist
}
```

**Note:** `has()` returns `false` if the value is an empty string.

<a id="check-input-filled"></a>
### Check Input Filled

**Check if input exists and has a value:**

```php
// Single check
if (Input::filled('name')) {
    echo 'Name is filled';
}

// Multiple check
if (Input::filled('name', 'email', 'phone')) {
    echo 'All fields are filled';
}
```

<a id="check-input-empty"></a>
### Check Input Empty

**Check if input is empty:**

```php
if (Input::unfilled('optional_field')) {
    echo 'Field is empty or not present';
}

// Multiple check
if (Input::unfilled('field1', 'field2')) {
    echo 'All fields are empty';
}
```

<a id="query-string"></a>
## Query String

**Get parameters from query string:**

```php
// URL: /search?q=keyword&page=2

$query = Input::query('q');
// "keyword"

$page = Input::query('page', 1);
// 2

// Get all query parameters
$params = Input::query();
// ['q' => 'keyword', 'page' => 2]
```

<a id="json-input"></a>
## JSON Input

**Get JSON input from request body:**

```php
// Request Content-Type: application/json
// Body: {"name": "John", "email": "john@example.com"}

// As object
$data = Input::json();
echo $data->name; // "John"
echo $data->email; // "john@example.com"

// As array
$data = Input::json(false);
echo $data['name']; // "John"
```

**Example API endpoint:**

```php
Route::post('api/users', function () {
    $data = Input::json(false);
    
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
    ]);
    
    return Response::json($user, 201);
});
```

<a id="file-upload"></a>
## File Upload

<a id="get-uploaded-file"></a>
### Get Uploaded File

**Get file from input:**

```php
// Get file object
$file = Input::file('avatar');

// Get all files
$files = Input::file();
```

<a id="check-file-exists"></a>
### Check File Exists

```php
if (Input::has_file('avatar')) {
    $file = Input::file('avatar');
    // Process file
}
```

<a id="upload-file"></a>
### Upload File

**Upload file to directory:**

```php
// Upload with auto-generated name
$filename = Input::upload('avatar', path('storage') . 'uploads');

// Upload with custom name
$filename = Input::upload('avatar', path('storage') . 'uploads', 'custom-name.jpg');
```

**Complete example:**

```php
Route::post('profile/avatar', function () {
    if (!Input::has_file('avatar')) {
        return Redirect::back()->with('error', 'No file uploaded');
    }
    
    // Validate file
    $rules = ['avatar' => 'required|image|max:2048'];
    $validation = Validator::make(Input::all(), $rules);
    
    if ($validation->fails()) {
        return Redirect::back()->with_errors($validation);
    }
    
    // Upload file
    $filename = Input::upload('avatar', path('storage') . 'avatars');
    
    // Update user
    Auth::user()->avatar = $filename;
    Auth::user()->save();
    
    return Redirect::back()->with('message', 'Avatar updated!');
});
```

<a id="file-information"></a>
### File Information

**Get file information:**

```php
$file = Input::file('document');

// File name
$name = $file['name'];

// File type
$type = $file['type'];

// File size (bytes)
$size = $file['size'];

// Temporary path
$tmpPath = $file['tmp_name'];

// Error code
$error = $file['error'];
```

**Specific information:**

```php
// Get size only
$size = Input::file('document.size');

// Get type only
$type = Input::file('document.type');

// Get name only
$name = Input::file('document.name');
```

<a id="file-validation"></a>
### File Validation

```php
Route::post('upload', function () {
    $rules = [
        'document' => 'required|mimes:pdf,doc,docx|max:5120', // 5MB
        'image' => 'required|image|max:2048', // 2MB
    ];
    
    $validation = Validator::make(Input::all(), $rules);
    
    if ($validation->fails()) {
        return Redirect::back()
            ->with_input()
            ->with_errors($validation);
    }
    
    // Upload files
    $docPath = Input::upload('document', path('storage') . 'documents');
    $imgPath = Input::upload('image', path('storage') . 'images');
    
    return Redirect::back()->with('message', 'Files uploaded!');
});
```

<a id="old-input"></a>
## Old Input

<a id="flash-input"></a>
### Flash Input

**Flash input to session:**

```php
// Flash all input
Input::flash();

// Use in form to preserve input when validation fails
return Redirect::back()->with_input();
```

<a id="retrieve-old-input"></a>
### Retrieve Old Input

**Get old input from flash data:**

```php
$name = Input::old('name');
$email = Input::old('email');

// With default value
$country = Input::old('country', 'ID');
```

**In view:**

```php
<input type="text" name="name" value="<?php echo Input::old('name'); ?>">
<input type="email" name="email" value="<?php echo Input::old('email'); ?>">
```

**With Blade:**

```blade
<input type="text" name="name" value="{{ Input::old('name') }}">
<input type="email" name="email" value="{{ Input::old('email') }}">
```

**Check old input exists:**

```php
if (Input::had('email')) {
    echo 'Email was submitted before';
}
```

<a id="flash-with-filter"></a>
### Flash with Filter

**Flash only specific input:**

```php
// Flash only
Input::flash('only', ['name', 'email']);

// Flash except
Input::flash('except', ['password', '_token']);
```

<a id="clear-old-input"></a>
### Clear Old Input

```php
// Clear old input from session
Input::flush();
```

<a id="redirect-with-old-input"></a>
## Redirect with Old Input

**Common pattern for form validation:**

```php
Route::post('register', function () {
    $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed',
    ];
    
    $validation = Validator::make(Input::all(), $rules);
    
    if ($validation->fails()) {
        return Redirect::back()
            ->with_input()  // Flash input
            ->with_errors($validation);
    }
    
    // Create user
    $user = User::create(Input::all());
    
    return Redirect::to('login')
        ->with('message', 'Registration successful!');
});
```

**In view to show old input:**

```php
<form method="POST" action="/register">
    <input type="text" name="name" value="<?php echo Input::old('name'); ?>">
    <?php if ($errors->has('name')): ?>
        <span class="error"><?php echo $errors->first('name'); ?></span>
    <?php endif; ?>
    
    <input type="email" name="email" value="<?php echo Input::old('email'); ?>">
    <?php if ($errors->has('email')): ?>
        <span class="error"><?php echo $errors->first('email'); ?></span>
    <?php endif; ?>
    
    <input type="password" name="password">
    <?php if ($errors->has('password')): ?>
        <span class="error"><?php echo $errors->first('password'); ?></span>
    <?php endif; ?>
    
    <button type="submit">Register</button>
</form>
```

<a id="input-manipulation"></a>
## Input Manipulation

<a id="merge-input"></a>
### Merge Input

**Merge data into input:**

```php
// Add/merge data
Input::merge(['role' => 'user', 'active' => 1]);

// Now Input::get('role') will return 'user'
```

**Example:**

```php
Route::post('users', function () {
    // Auto-add timestamps and user_id
    Input::merge([
        'user_id' => Auth::user()->id,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    
    User::create(Input::all());
});
```

<a id="replace-input"></a>
### Replace Input

**Replace all input:**

```php
// Replace entire input
Input::replace([
    'name' => 'New Name',
    'email' => 'new@example.com',
]);
```

<a id="clear-input"></a>
### Clear Input

```php
// Clear all input
Input::clear();
```

<a id="cookies"></a>
## Cookies

<a id="get-cookie"></a>
### Get Cookie

**Get cookie value:**

```php
$value = Cookie::get('name');

// With default value
$theme = Cookie::get('theme', 'light');
```

**Check cookie exists:**

```php
if (Cookie::has('user_preferences')) {
    $prefs = Cookie::get('user_preferences');
}
```

<a id="set-cookie"></a>
### Set Cookie

**Set cookie with expiration:**

```php
// Set cookie (expires in 60 minutes)
Cookie::put('name', 'value', 60);

// Set cookie with path and domain
Cookie::put('name', 'value', 60, '/', '.example.com');

// Set secure cookie (HTTPS only)
Cookie::put('name', 'value', 60, '/', null, true);

// Set httponly cookie
Cookie::put('name', 'value', 60, '/', null, false, true);
```

<a id="delete-cookie"></a>
### Delete Cookie

**Forget (delete) cookie:**

```php
Cookie::forget('name');

// Or set with expiration 0
Cookie::put('name', '', 0);
```

<a id="forever-cookie"></a>
### Forever Cookie

**Set cookie that never expires (5 years):**

```php
Cookie::forever('remember_token', $token);

// Same as
Cookie::put('remember_token', $token, 2628000); // 5 years in minutes
```

<a id="practical-examples"></a>
## Practical Examples

**Form with validation and old input:**

```php
// Controller
Route::post('contact', function () {
    $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email',
        'message' => 'required|min:10',
    ];
    
    $validation = Validator::make(Input::all(), $rules);
    
    if ($validation->fails()) {
        return Redirect::back()
            ->with_input()
            ->with_errors($validation);
    }
    
    // Send email
    Email::send('emails.contact', Input::all(), function ($message) {
        $message->to('admin@example.com')
                ->subject('New Contact Message');
    });
    
    return Redirect::back()
        ->with('message', 'Message sent successfully!');
});
```

**View:**

```php
<form method="POST" action="/contact">
    <?php if (Session::has('message')): ?>
        <div class="alert alert-success">
            <?php echo Session::get('message'); ?>
        </div>
    <?php endif; ?>
    
    <div>
        <label>Name:</label>
        <input type="text" name="name" value="<?php echo Input::old('name'); ?>">
        <?php if ($errors->has('name')): ?>
            <span class="error"><?php echo $errors->first('name'); ?></span>
        <?php endif; ?>
    </div>
    
    <div>
        <label>Email:</label>
        <input type="email" name="email" value="<?php echo Input::old('email'); ?>">
        <?php if ($errors->has('email')): ?>
            <span class="error"><?php echo $errors->first('email'); ?></span>
        <?php endif; ?>
    </div>
    
    <div>
        <label>Message:</label>
        <textarea name="message"><?php echo Input::old('message'); ?></textarea>
        <?php if ($errors->has('message')): ?>
            <span class="error"><?php echo $errors->first('message'); ?></span>
        <?php endif; ?>
    </div>
    
    <button type="submit">Send</button>
</form>
```

**File upload with preview:**

```php
Route::post('products', function () {
    $rules = [
        'name' => 'required',
        'price' => 'required|numeric',
        'image' => 'required|image|max:2048',
    ];
    
    $validation = Validator::make(Input::all(), $rules);
    
    if ($validation->fails()) {
        return Redirect::back()
            ->with_input(Input::except('image'))
            ->with_errors($validation);
    }
    
    // Upload image
    $imagePath = Input::upload('image', path('public') . 'products');
    
    // Create product
    Product::create([
        'name' => Input::get('name'),
        'price' => Input::get('price'),
        'image' => $imagePath,
    ]);
    
    return Redirect::to('products')
        ->with('message', 'Product created!');
});
```

**API with JSON input:**

```php
Route::post('api/posts', function () {
    // Get JSON input
    $data = Input::json(false);
    
    // Validate
    $rules = [
        'title' => 'required|max:255',
        'content' => 'required',
    ];
    
    $validation = Validator::make($data, $rules);
    
    if ($validation->fails()) {
        return Response::json([
            'error' => 'Validation failed',
            'messages' => $validation->errors->all(),
        ], 422);
    }
    
    // Create post
    $post = Post::create($data);
    
    return Response::json($post, 201);
});
```

**Remember user preferences with cookie:**

```php
// Save preference
Route::post('preferences', function () {
    $theme = Input::get('theme', 'light');
    $language = Input::get('language', 'id');
    
    Cookie::put('theme', $theme, 43200); // 30 days
    Cookie::put('language', $language, 43200);
    
    return Redirect::back()->with('message', 'Preferences saved!');
});

// Load preference
$theme = Cookie::get('theme', 'light');
$language = Cookie::get('language', 'id');

View::share('theme', $theme);
View::share('language', $language);
```

**Search with query string:**

```php
Route::get('products', function () {
    $query = Product::query();
    
    // Search
    if (Input::has('q')) {
        $keyword = Input::get('q');
        $query->where('name', 'like', "%$keyword%");
    }
    
    // Filter by category
    if (Input::has('category')) {
        $query->where('category_id', Input::get('category'));
    }
    
    // Sort
    $sortBy = Input::get('sort', 'created_at');
    $sortDir = Input::get('dir', 'desc');
    $query->order_by($sortBy, $sortDir);
    
    // Paginate
    $perPage = Input::get('per_page', 20);
    $products = $query->paginate($perPage);
    
    return View::make('products.index', compact('products'));
});
```

**Bulk operations:**

```php
Route::post('users/bulk-action', function () {
    $action = Input::get('action');
    $userIds = Input::get('users', []);
    
    if (empty($userIds)) {
        return Redirect::back()->with('error', 'No users selected');
    }
    
    switch ($action) {
        case 'activate':
            User::where_in('id', $userIds)->update(['active' => 1]);
            break;
            
        case 'deactivate':
            User::where_in('id', $userIds)->update(['active' => 0]);
            break;
            
        case 'delete':
            User::where_in('id', $userIds)->delete();
            break;
    }
    
    return Redirect::back()->with('message', 'Action completed!');
});
```
