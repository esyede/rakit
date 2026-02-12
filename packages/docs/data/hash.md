# Hash

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Hashing Password](#hashing-password)
- [Verifying Password](#verifying-password)
- [Checking Hash Strength](#checking-hash-strength)
- [Best Practices](#best-practices)
- [Usage Examples](#usage-examples)
  - [User Registration](#user-registration)
  - [User Login](#user-login)
  - [Update Password](#update-password)
  - [Migrating Old Hashes](#migrating-old-hashes)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The `Hash` class provides a secure way to hash and verify passwords using the bcrypt algorithm. Bcrypt is a hashing algorithm designed to be slow, making it very difficult to crack using brute-force attacks.

This class uses PHP's `crypt()` function with the bcrypt (Blowfish) algorithm, which is the industry standard for password hashing.

<a id="hashing-password"></a>
## Hashing Password

Use the `make()` method to hash a password:

```php
$password = 'secret123';
$hash = Hash::make($password);

echo $hash;
// $2y$10$8K1p/H0ezvZ92.QsIWYpBuXxC5z8VgN3LWbGGqN1VHFnVnvSKNmT6
```

### Setting Cost Factor

The second parameter of the `make()` method is the cost factor that determines how much computation time is required for hashing. The default value is 10, with a range of 4-31:

```php
// Default cost factor (10)
$hash = Hash::make($password);

// Higher cost factor = more secure but slower
$hash = Hash::make($password, 12);

// Lower cost factor = faster but less secure
$hash = Hash::make($password, 8);
```

> **Note:** Each increase of 1 in the cost factor doubles the hashing time. Use a balanced value between security and performance.

<a id="verifying-password"></a>
## Verifying Password

Use the `check()` method to verify that a plain-text password matches the stored hash:

```php
$password = 'secret123';
$hash = '$2y$10$8K1p/H0ezvZ92.QsIWYpBuXxC5z8VgN3LWbGGqN1VHFnVnvSKNmT6';

if (Hash::check($password, $hash)) {
    echo 'Password is correct!';
} else {
    echo 'Password is incorrect!';
}
```

This method uses constant-time comparison to prevent timing attacks.

<a id="checking-hash-strength"></a>
## Checking Hash Strength

The `weak()` method checks if a hash uses a cost factor lower than desired:

```php
$hash = '$2y$08$...'; // Hash with cost factor 8

// Check if hash is weak (cost < 10)
if (Hash::weak($hash)) {
    echo 'This hash is weak, needs rehashing';
}

// Check with custom cost factor
if (Hash::weak($hash, 12)) {
    echo 'This hash is weak, needs rehashing with cost 12';
}
```

This is useful for systems that want to upgrade hash strength over time.

<a id="best-practices"></a>
## Best Practices

1. **Never store passwords in plain-text**
   ```php
   // DON'T:
   $user->password = $password;
   
   // DO:
   $user->password = Hash::make($password);
   ```

2. **Don't limit password length**
   ```php
   // DON'T:
   $rules = ['password' => 'required|max:20'];
   
   // DO:
   $rules = ['password' => 'required|min:8'];
   ```

3. **Use appropriate cost factor**
   ```php
   // For production, use cost 10-12
   $hash = Hash::make($password, 12);
   ```

4. **Automatically rehash old passwords**
   ```php
   if (Hash::check($password, $user->password)) {
       // Login successful
       
       // Rehash if hash is weak
       if (Hash::weak($user->password, 12)) {
           $user->password = Hash::make($password, 12);
           $user->save();
       }
   }
   ```

5. **Don't use hash for data other than passwords**
   ```php
   // DON'T use Hash for tokens, API keys, etc.
   // Use Hash ONLY for passwords
   ```

<a id="usage-examples"></a>
## Usage Examples

<a id="user-registration"></a>
### User Registration

```php
Route::post('register', function () {
    $rules = [
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed',
    ];
    
    $validation = Validator::make(Input::all(), $rules);
    
    if ($validation->fails()) {
        return Redirect::back()
            ->with_input()
            ->with_errors($validation);
    }
    
    // Hash password before saving
    $user = new User;
    $user->name = Input::get('name');
    $user->email = Input::get('email');
    $user->password = Hash::make(Input::get('password'));
    $user->save();
    
    return Redirect::to('login')
        ->with('message', 'Registration successful!');
});
```

<a id="user-login"></a>
### User Login

```php
Route::post('login', function () {
    $email = Input::get('email');
    $password = Input::get('password');
    
    // Get user from database
    $user = User::where('email', '=', $email)->first();
    
    // Check if user exists and password matches
    if ($user && Hash::check($password, $user->password)) {
        // Login successful
        Auth::login($user->id);
        
        return Redirect::to('dashboard');
    }
    
    // Login failed
    return Redirect::back()
        ->with_input()
        ->with('error', 'Email or password is incorrect');
});
```

<a id="update-password"></a>
### Update Password

```php
Route::post('profile/password', function () {
    $rules = [
        'current_password' => 'required',
        'new_password' => 'required|min:8|confirmed',
    ];
    
    $validation = Validator::make(Input::all(), $rules);
    
    if ($validation->fails()) {
        return Redirect::back()->with_errors($validation);
    }
    
    $user = Auth::user();
    
    // Verify old password
    if (!Hash::check(Input::get('current_password'), $user->password)) {
        return Redirect::back()
            ->with('error', 'Old password does not match');
    }
    
    // Update password
    $user->password = Hash::make(Input::get('new_password'));
    $user->save();
    
    return Redirect::back()
        ->with('message', 'Password changed successfully');
});
```

<a id="migrating-old-hashes"></a>
### Migrating Old Hashes

If you want to upgrade the cost factor or migrate from old hashing algorithms:

```php
Route::post('login', function () {
    $email = Input::get('email');
    $password = Input::get('password');
    
    $user = User::where('email', '=', $email)->first();
    
    if (!$user) {
        return Redirect::back()->with('error', 'User not found');
    }
    
    // Check if using old hash (e.g., MD5 or SHA1)
    if (strlen($user->password) === 32 || strlen($user->password) === 40) {
        // Old hash (MD5 or SHA1)
        $old_hash = ($user->password_algo === 'md5') 
            ? md5($password) 
            : sha1($password);
        
        if ($old_hash === $user->password) {
            // Password matches, upgrade to bcrypt
            $user->password = Hash::make($password, 12);
            $user->password_algo = 'bcrypt';
            $user->save();
            
            Auth::login($user->id);
            return Redirect::to('dashboard');
        }
    } else {
        // Bcrypt hash
        if (Hash::check($password, $user->password)) {
            // Rehash if cost factor is weak
            if (Hash::weak($user->password, 12)) {
                $user->password = Hash::make($password, 12);
                $user->save();
            }
            
            Auth::login($user->id);
            return Redirect::to('dashboard');
        }
    }
    
    return Redirect::back()->with('error', 'Incorrect password');
});
```

With mutator in the model:

```php
class User extends Facile
{
    /**
     * Automatically hash password when saved
     */
    public function set_password($password)
    {
        if (!empty($password)) {
            $this->set_attribute('password', Hash::make($password));
        }
    }
}

// Now password is automatically hashed
$user = new User;
$user->password = 'secret123'; // Automatically hashed
$user->save();
```
