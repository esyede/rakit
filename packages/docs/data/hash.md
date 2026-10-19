# Hash

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Hashing Password](#hashing-password)
  - [Setting Cost Factor](#setting-cost-factor)
- [Verifying Password](#verifying-password)
- [Checking Hash Strength](#checking-hash-strength)
- [Best Practices](#best-practices)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

`Hash` hashes and verifies passwords with bcrypt (via PHP's `crypt()`). Bcrypt is deliberately slow, which makes brute-force attacks expensive. Use it for passwords only.

<a id="hashing-password"></a>
## Hashing Password

```php
$hash = Hash::make('secret123');
// $2y$10$8K1p/H0ezvZ92.QsIWYpBuXxC5z8VgN3LWbGGqN1VHFnVnvSKNmT6
```

<a id="setting-cost-factor"></a>
### Setting Cost Factor

The second parameter is the cost factor: an integer from 4 to 31, default 10. Each step up doubles the hashing time, so balance security against response time (10–12 is typical for production):

```php
$hash = Hash::make($password, 12);
```

An exception is thrown if the cost is out of range or the password is not a string (`null` and integers are cast to string).

<a id="verifying-password"></a>
## Verifying Password

`check()` verifies a plain-text password against a stored hash, using a constant-time comparison:

```php
if (Hash::check('secret123', $user->password)) {
    // Password is correct
}
```

<a id="checking-hash-strength"></a>
## Checking Hash Strength

`weak()` returns `true` if a bcrypt hash uses a cost factor different from the given one (default 10). It returns `false` for anything that is not a 60-character `$2y$` hash. Use it to rehash passwords on login when you raise the cost:

```php
if (Hash::check($password, $user->password)) {
    if (Hash::weak($user->password, 12)) {
        $user->password = Hash::make($password, 12);
        $user->save();
    }
}
```

<a id="best-practices"></a>
<a id="usage-examples"></a>
<a id="user-registration"></a>
<a id="user-login"></a>
<a id="update-password"></a>
<a id="migrating-old-hashes"></a>
## Best Practices

- Never store passwords in plain text; always store `Hash::make($password)`.
- Don't cap password length with rules like `max:20`; set a minimum instead (`min:8`).
- Don't use `Hash` for tokens or API keys; it is meant for passwords only.
- Hash automatically in your model with a mutator:

```php
class User extends Facile
{
    public function set_password($password)
    {
        if (! empty($password)) {
            $this->set_attribute('password', Hash::make($password));
        }
    }
}

$user = new User;
$user->password = 'secret123'; // stored hashed
$user->save();
```
