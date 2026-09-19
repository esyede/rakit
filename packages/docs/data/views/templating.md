# Templating

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Section](#section)
-   [Blade Template Engine](#blade-template-engine)
-   [Blade Conditionals & Looping](#blade-conditionals--looping)
-   [Blade Layout](#blade-layout)
-   [Stacks](#stacks)
-   [Components](#components)
-   [Other Directives](#other-directives)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Most pages of an application share one layout. A controller can name it once,
instead of every action rebuilding it:

#### Add the `$layout` property to your controller:

```php
class Home_Controller extends Controller
{
	public $layout = 'layouts.common';

	// ..
}
```

> Rakit turns the `$layout` property into a `View` instance.

#### Access its layout from the action in the controller:

```php
public function action_profile()
{
	$this->layout->nest('content', 'user.profile');
}
```

> An action using a layout does not have to return anything.

**Complete example:**

```php
class Home_Controller extends Controller
{
    public $layout = 'layouts.master';

    public function action_index()
    {
        $this->layout->title = 'Home Page';
        $this->layout->nest('content', 'home.index');
    }

    public function action_about()
    {
        $this->layout->title = 'About Us';
        $this->layout->nest('content', 'home.about', ['team' => $team]);
    }
}
```

<a id="section"></a>

## Section

Sections let a nested view put content into the layout, such as the JavaScript that
view needs in the layout header:

#### Creating a section in the view:

```php
<?php Section::start('scripts'); ?>
	<script src="<?php echo asset('js/jquery.js'); ?>"></script>
<?php Section::stop(); ?>
```

#### Displaying the content of a section:

```php
<head>
	<?php echo Section::yield_content('scripts'); ?>
</head>
```

#### Using Blade syntax to create a section:

```blade
@section('scripts')
	<script src="jquery.js"></script>
@endsection

<head>
	@yield('scripts')
</head>
```

<a id="blade-template-engine"></a>

## Blade Template Engine

A view with the `.blade.php` extension is compiled by Blade, which gives it a shorter
syntax for printing data and for the usual control structures.

#### Displaying variables using Blade:

```blade
Hello, {{ $name }}.
```

#### Displaying function results using Blade:

```blade
{{ now() }}
```

> The `{{` `}}` syntax is automatically escaped via
the [htmlentities()](https://www.php.net/manual/en/function.htmlentities.php) function so it is safe from XSS attacks.

#### Blade & JavaScript Frameworks

JavaScript frameworks use curly braces too. An `@` in front tells Blade to leave the
expression alone:

```blade
Hello, @{{ $name }}.
```

Blade drops the `@` and leaves `{{ $name }}` for the front end to render.

#### Displaying data with default value

A variable that may not be defined can be written out in full:

```blade
{{ isset($name) ? $name : 'Guest' }}
```

Or with the Blade shortcut:

```blade
{{ $name or 'Guest' }}
```

#### Displaying data without escaping

By default, data enclosed by the `{{ }}` syntax will be automatically escaped
using the [htmlentities](https://www.php.net/manual/en/function.htmlentities.php) function to prevent XSS attacks.

If you do not want your data to be escaped, you can use the following syntax:

```blade
Hello, {!! $name !!}
```

> Be careful when displaying data from user input. Always use the `{{` `}}` syntax to avoid any HTML entities in the data.

#### Displaying a view:

`@include()` imports a view into another one, handing it all the data of the current view.

```blade
<h1>Profile</h1>

@include('user.profile')
```

`@render()` does the same, except the rendered view **inherits no data**.

```blade
@render('admin.list')
```

**Difference between `@include()` and `@render()`:**

```blade
// @include() - view inherits all data from parent
@include('partials.user', ['user' => $user])
// View 'partials.user' can access $user variable and all variables from parent

// @render() - view only receives given data
@render('partials.user', ['user' => $user])
// View 'partials.user' can only access $user variable
```

#### Creating comments:

```blade
{{-- This is a one-line comment --}}

{{--
	This is
	multiple lines comment.
	write as much as you need.
--}}
```

> Unlike HTML comments, Blade comments are not visible when view-source.

<a id="blade-conditionals--looping"></a>

## Blade Conditionals & Looping

#### If Statement:

```blade
@if (5000 === $price)
    Wow, the price is 5 thousand!
@endif
```

#### If Else Statement:

```blade
@if (count($messages) > 0)
    There are new messages!
@else
    No new messages!
@endif
```

#### Else If Statement:

```blade
@if ('male' === $gender)
    Hello sir!
@elseif ('female' === $gender)
    Hello madam!
@else
    Eh? what creature is this?
@endif
```

#### Unless Statement:

```blade
@unless(Auth::check())
    Please login first!
@endunless

// same as..

<?php if (! Auth::check()): ?>
    Please login first!
<?php endif; ?>
```

#### Set Statement:

```blade
@set('name', 'Budi')

// same as..

<?php $name = 'Budi'; ?>
```

#### For Loop:

```blade
@for ($i = 0; $i < 10; $i++)
    The current number is: {{ $i }}
@endfor
```

#### Foreach Loop:

```blade
@foreach ($users as $user)
    <p>The current user ID is: {{ $user->id }}</p>
@endforeach
```

#### For Else Loop:

```blade
@forelse ($users as $user)
    <li>{{ $user->name }}</li>
@empty
    <p>No users</p>
@endforelse
```

#### While Loop:

```blade
@while (true)
    <p>I am an infinite loop. Hahaha</p>
@endwhile
```

#### PHP Block:

```blade
@php
	$name = 'Angga';
	echo 'Hello '.$name;
@endphp

// same as..

<?php
	$name = 'Angga';
	echo 'Hello '.$name;
?>
```

<a id="blade-layout"></a>

## Blade Layout

**Template inheritance** lets one master layout be extended by the views that fill it.
A `'master'` view giving the application its consistent look:

**File: `application/views/master.blade.php`**

```blade
<html>
	<ul class="navigation">
		@section('navigation')
			<li><a href="home">Home</a></li>
			<li><a href="profile">Profile</a></li>
		@show
	</ul>

	<div class="content">
		@yield('content')
	</div>
</html>
```

`@show` closes the section and prints it in place, which is what a layout wants:
`@endsection` only defines a section, it does not print one.

The `'content'` section is what another view fills in:

**File: `application/views/profile.blade.php`**

```blade
@layout('master')

@section('content')
	Welcome to the profile page!
@endsection
```

Then return the `profile` view from a route:

```php
return View::make('profile');
```

The `profile` view will automatically use the `master` template thanks to the `@layout()` tag.

> The `@layout()` call must always be on the very first line of the view file, without whitespace or newline.

**Example of using layout in controller:**

```php
class User_Controller extends Controller
{
    public function action_profile($id)
    {
        $user = User::find($id);

        // View 'profile' will automatically use layout 'master'
        return View::make('profile', compact('user'));
    }
}
```

#### Adding content using `@parent`

A section can be added to rather than replaced. `@parent` stands for whatever the
layout put there, so the navigation list of the `master` layout
[above](#blade-layout) keeps its two links and gains a `Contact` one:

```blade
@layout('master')

@section('navigation')
	@parent
	<li><a href="contact">Contact</a></li>
@endsection

@section('content')
	Welcome to the profile page!
@endsection
```

**Final result:**

When the `profile` view is rendered, the `navigation` section will contain:

```html
<li><a href="home">Home</a></li>
<li><a href="profile">Profile</a></li>
<li><a href="contact">Contact</a></li>
```

Without `@parent`, the navigation section would be completely replaced with the new content, not added.

<a id="stacks"></a>

## Stacks

A stack collects content from several views into one place. Push from anywhere,
render it once in the layout:

```blade
{{-- application/views/profile.blade.php --}}
@push('scripts')
    <script src="profile.js"></script>
@endpush
```

```blade
{{-- application/views/master.blade.php --}}
<body>
    @yield('content')

    @stack('scripts')
</body>
```

Everything pushed to the same stack is rendered in the order it was pushed.

<a id="components"></a>

## Components

A component is a piece of markup with a name, kept in one place and used with a
tag of its own. Put its view in `application/views/components/`:

```html
<!-- application/views/components/alert.blade.php -->

@props(['type' => 'info'])

<div class="alert alert-{{ $type }}" {{ $attributes }}>
    {{ $slot }}
</div>
```

Then use it anywhere:

```html
<x-alert type="error">
    Data failed to save.
</x-alert>
```

```html
<div class="alert alert-error">
    Data failed to save.
</div>
```

A component with nothing inside it closes itself:

```html
<x-alert type="ok" />
```

A component in a subdirectory is named with a dot, so
`application/views/components/forms/input.blade.php` is:

```html
<x-forms.input name="email" />
```

### Attributes

Every attribute of the tag reaches the view. `@props` says which of them the
component expects, and what to use when the tag leaves one out:

```html
@props(['type' => 'info', 'dismissible' => false])
```

Write an attribute with a colon in front of its name to hand it an expression
instead of a piece of text:

```html
<x-alert :type="$status" :dismissible="$user->admin">
    {{ $message }}
</x-alert>
```

An attribute with no value at all is `TRUE`:

```html
<x-forms.input name="email" required />
```

### The Attribute Bag

Whatever `@props` did not name stays in `$attributes`, so a component can be
given the odd `id` or `data-` attribute without knowing about it in advance:

```html
<x-alert type="error" id="pesan-1" data-auto-close="3">Gagal</x-alert>
```

```html
<div class="alert alert-error" id="pesan-1" data-auto-close="3">Gagal</div>
```

`merge()` puts your own values underneath the ones the tag gave. A `class` is
joined rather than replaced, since a class list is meant to grow:

```html
<input {{ $attributes->merge(['class' => 'form-control', 'type' => 'text']) }}>
```

The bag also answers `get()`, `has()`, `only()`, `except()`, `starting_with()`
and `class_names()`:

```html
<div {{ $attributes->class_names(['card', 'card-active' => $active]) }}>
```

### Slots

What sits between the tags arrives as `$slot`. A component may take more than
one piece by naming them:

```html
<!-- application/views/components/card.blade.php -->

<div class="card">
    <div class="card-title">{{ $title }}</div>
    <div class="card-body">{{ $slot }}</div>
</div>
```

```html
<x-card>
    <x-slot name="title">Laporan Bulanan</x-slot>

    Isi laporan ada di sini.
</x-card>
```

`<x-slot:title>` is the shorter way to write the same thing.

A slot knows whether it was given anything:

```html
@if ($slot->is_empty())
    <p>Belum ada isi.</p>
@else
    {{ $slot }}
@endif
```

> A slot carries markup, so `{{ $slot }}` prints it as it is. That is safe:
> whatever the slot holds was already escaped on its way in, so `{{ $name }}`
> written inside a slot is still escaped exactly once.

### Components with a Class

A component that needs to work something out first gets a class of its own in
`application/components/`. There are no namespaces to keep class names apart, so
the name carries `_Component` behind it, the same way a controller carries
`_Controller`. `Component` is the alias of the class it extends, and it is
registered in `application/config/aliases.php` like every other one:

```php
// application/components/badge.php

class Badge_Component extends Component
{
    public $label = '';

    public $colour = 'grey';

    public function render()
    {
        return 'components.badge';
    }
}
```

Every public property is handed to the view, and an attribute of the same name
takes its place. `render()` answers with the name of a view, or with the markup
itself:

```php
public function render()
{
    return '<span class="badge">' . e($this->label) . '</span>';
}
```

A class that does not follow the naming can be registered by hand:

```php
// application/boot.php

Component::register('badge', 'My_Own_BadgeComponent');
```

`make:component` writes both the class and its view for you:

```bash
php rakit make:component alert
```

### Components of a Package

A package names its components the way it names its views, with the double
colon:

```html
<x-blog::alert type="error">Gagal</x-blog::alert>
```

The view is read from `packages/blog/views/components/alert.blade.php`, and the
class behind it, if there is one, is `Blog_Alert_Component` in
`packages/blog/components/alert.php` — the package prefix in front, the same
prefix a package puts on its controllers.

<a id="other-directives"></a>

## Other Directives

| Directive                        | Description                                                                     |
| -------------------------------- | ------------------------------------------------------------------------------- |
| `@csrf`                          | Print a hidden CSRF token field                                                 |
| `@method('put')`                 | Print a hidden `_method` field to spoof the HTTP method (now escaped; dynamic values are `e()`-escaped) |
| `@json($data)`                   | Print a variable as JSON, handy for passing data to JavaScript                  |
| `@auth` .. `@endauth`            | Content that is only shown to a logged-in user                                  |
| `@guest` .. `@endguest`          | Content that is only shown to a guest                                           |
| `@error('field')` .. `@enderror` | Content that is only shown when the field has a validation error                |
| `@hassection('name')` .. `@endif`| Content that is only shown when the section exists                              |
| `@sectionmissing('name')` .. `@endif` | Content that is only shown when the section does not exist                 |
| `@once` .. `@endonce`            | Content that is rendered only once, even when the view is included many times   |
| `@set('name', $value)`           | Set a variable inside the view                                                  |
| `@unset('name')`                 | Remove a variable                                                               |
| `@inject('name', $content)`      | Fill a section straight from a string, without `@section` .. `@endsection`      |
| `@show`                          | Close a section and print it right away                                         |
| `@verbatim` .. `@endverbatim`    | A block that Blade leaves untouched                                             |
| `@php` .. `@endphp`              | A block of plain PHP                                                            |
| `@render_each('view', $items, 'item')` | Render one view per item in the array                                     |

```blade
<form method="post" action="{{ url('user/1') }}">
    @csrf
    @method('put')

    <input name="email" value="{{ $user->email }}">
    @error('email')
        <span class="error">{{ $errors->first('email') }}</span>
    @enderror
</form>

@auth
    <p>Hello, {{ Auth::user()->name }}.</p>
@endauth

@guest
    <a href="{{ url('login') }}">Login</a>
@endguest

<script>
    var user = @json($user);
</script>
```
