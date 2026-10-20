# Authorization

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Defining Abilities](#defining-abilities)
-   [Checking Abilities](#checking-abilities)
-   [Guests](#guests)
-   [Before and After Callbacks](#before-and-after-callbacks)
-   [Policies](#policies)
-   [Protecting Routes](#protecting-routes)
-   [In Views](#in-views)
-   [Another User](#another-user)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

[Authentication](/docs/auth/usage) tells you **who** the user is; `Gate` decides **what** they may do. An ability is a name and a callback answering `true` or `false`; a policy groups the abilities of one model in a class. Anything that is not defined is denied.

Define abilities and policies once, in `application/boot.php`. They are kept for the life of the process, which in [worker mode](/docs/workers) means for every request, so do not define them inside a route.

<a id="defining-abilities"></a>

## Defining Abilities

The callback receives the current user first, then the arguments of the check:

```php
Gate::define('update-post', function ($user, $post) {
    return $user->id === $post->user_id;
});

// Or a 'Class@method' string; the class is created for each check.
Gate::define('update-post', 'PostAbilities@update');
```

<a id="checking-abilities"></a>

## Checking Abilities

```php
if (Gate::allows('update-post', $post)) {
    // ..
}

Gate::denies('update-post', $post);
Gate::check(['update-post', 'delete-post'], $post); // every one
Gate::any(['update-post', 'delete-post'], $post);   // at least one
Gate::none(['update-post', 'delete-post'], $post);  // not a single one
Gate::has('update-post');                            // defined?
```

Pass several arguments as an array: `Gate::allows('move-post', [$post, $category])`.

`authorize()` answers **403** unless the ability is granted, and returns `true` otherwise:

```php
Gate::authorize('update-post', $post);
```

<a id="guests"></a>

## Guests

When nobody is logged in, a callback only runs if its user parameter is optional. Otherwise the check is denied without calling it:

```php
Gate::define('view-post', function ($user = null, $post = null) {
    return $post->published || ($user && $user->id === $post->user_id);
});
```

<a id="before-and-after-callbacks"></a>

## Before and After Callbacks

A `before` callback runs ahead of every check. Returning anything but `null` decides it, which suits administrators:

```php
Gate::before(function ($user, $ability) {
    if ($user->is_admin) {
        return true;
    }
});
```

An `after` callback runs once the check is done, and only decides when nothing else did:

```php
Gate::after(function ($user, $ability, $result, $arguments) {
    // log the decision, or answer for undefined abilities
});
```

<a id="policies"></a>

## Policies

A policy is a plain class whose method names are abilities. Register it for a model class; it also answers for subclasses:

```php
class Post_Policy
{
    // Optional: a non-NULL answer here decides every ability of the policy.
    public function before($user, $ability)
    {
        if ($user->banned) {
            return false;
        }
    }

    public function update($user, $post)
    {
        return $user->id === $post->user_id;
    }

    public function create($user)
    {
        return $user->verified;
    }
}

Gate::policy('Post', 'Post_Policy');
```

The policy is picked by the first argument. Pass the model for actions on a record, and the class name for actions without one:

```php
Gate::allows('update', $post);
Gate::allows('create', 'Post');
```

`Gate::get_policy_for($post)` returns the policy instance, or `null`.

<a id="protecting-routes"></a>

## Protecting Routes

The `can` middleware answers 403 when the ability is denied. A second parameter is passed to the check, so a class name reaches its policy:

```php
Route::get('admin', ['before' => 'auth|can:admin', function () {
    // ..
}]);

Route::post('posts', ['before' => 'auth|can:create,Post', 'uses' => 'posts@store']);
```

For checks on a record, call `Gate::authorize()` in the controller once the record is loaded.

<a id="in-views"></a>

## In Views

```blade
@can('update', $post)
    <a href="{{ url('posts/'.$post->id.'/edit') }}">Edit</a>
@endcan

@cannot('create', 'Post')
    <p>Verify your email to write posts.</p>
@endcannot

@canany(['update', 'delete'], $post)
    ...
@endcanany
```

<a id="another-user"></a>

## Another User

`for_user()` checks any user instead of the logged in one, for instance in a job, a console command or an API request authenticated by token. `null` checks a guest:

```php
Gate::for_user($user)->allows('update', $post);
Gate::for_user(null)->denies('update', $post);
```
