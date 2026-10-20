# Templating

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Displaying Data](#displaying-data)
    -   [Raw Output](#raw-output)
    -   [Blade & JavaScript Frameworks](#blade--javascript-frameworks)
    -   [Comments](#comments)
-   [Blade Conditionals & Looping](#blade-conditionals--looping)
    -   [The Loop Variable](#the-loop-variable)
    -   [Variables & PHP Blocks](#variables--php-blocks)
-   [Including Views](#including-views)
-   [Blade Layout](#blade-layout)
    -   [Extending a Section with @parent](#extending-a-section-with-parent)
    -   [Sections Without Blade](#sections-without-blade)
-   [Stacks](#stacks)
-   [Components](#components)
    -   [Attributes](#attributes)
    -   [The Attribute Bag](#the-attribute-bag)
    -   [Slots](#slots)
    -   [Components with a Class](#components-with-a-class)
    -   [Components of a Package](#components-of-a-package)
-   [Other Directives](#other-directives)
-   [Controller Layouts](#controller-layouts)
-   [Extending Blade](#extending-blade)

<!-- /MarkdownTOC -->

<a id="blade-template-engine"></a>
<a id="basic-knowledge"></a>

## Basic Knowledge

Blade is Rakit's template engine. Any view ending in `.blade.php` is compiled to
plain PHP (and cached in `storage/views`), giving you a short syntax for
printing data, control structures, layouts and components. Plain `.php` views
keep working alongside Blade ones.

<a id="displaying-data"></a>

## Displaying Data

Wrap any expression in `{{ }}` to print it. Output is escaped with
`htmlentities()`, so it is safe from XSS:

```blade
Hello, {{ $name }}. It is {{ now() }}.
```

Use `or` to print a default when a variable is not set:

```blade
{{ $name or 'Guest' }}
{{-- same as {{ isset($name) ? $name : 'Guest' }} --}}
```

<a id="raw-output"></a>

### Raw Output

`{!! !!}` prints without escaping:

```blade
Hello, {!! $name !!}
```

> Never use `{!! !!}` on user input.

<a id="blade--javascript-frameworks"></a>

### Blade & JavaScript Frameworks

Prefix `{{` with `@` to leave the expression for the front-end framework.
Blade only drops the `@`:

```blade
Hello, @{{ name }}.
```

For a larger block, wrap it in `@verbatim` .. `@endverbatim`.

<a id="comments"></a>

### Comments

Blade comments, unlike HTML comments, are removed from the output:

```blade
{{-- This comment will not be in the HTML --}}
```

<a id="blade-conditionals--looping"></a>

## Blade Conditionals & Looping

```blade
@if (count($messages) === 1)
    One new message.
@elseif (count($messages) > 1)
    Many new messages.
@else
    No new messages.
@endif

@unless (Auth::check())
    Please login first!
@endunless

@for ($i = 0; $i < 10; $i++)
    {{ $i }}
@endfor

@foreach ($users as $user)
    <p>{{ $user->name }}</p>
@endforeach

@forelse ($users as $user)
    <li>{{ $user->name }}</li>
@empty
    <p>No users</p>
@endforelse

@while ($item = array_pop($items))
    {{ $item }}
@endwhile
```

<a id="the-loop-variable"></a>

### The Loop Variable

Inside `@foreach`, a `$loop` object describes the current iteration:

```blade
@foreach ($users as $user)
    @if ($loop->first) <ul> @endif
    <li class="{{ $loop->odd ? 'odd' : 'even' }}">{{ $loop->iteration }}. {{ $user->name }}</li>
    @if ($loop->last) </ul> @endif
@endforeach
```

| Property     | Description                                     |
| ------------ | ----------------------------------------------- |
| `index`      | Zero-based index of the current iteration       |
| `iteration`  | Current iteration, starting at `1`              |
| `remaining`  | Iterations left                                 |
| `count`      | Total number of items                           |
| `first`      | Whether this is the first iteration             |
| `last`       | Whether this is the last iteration              |
| `even`/`odd` | Whether the iteration number is even / odd      |
| `depth`      | Nesting level of the loop (`0` for the outermost) |
| `parent`     | The parent loop's `$loop` in a nested loop      |

<a id="variables--php-blocks"></a>

### Variables & PHP Blocks

```blade
@set('name', 'Budi')   {{-- <?php $name = 'Budi'; ?> --}}
@unset('name')

@php($total = 0)

@php
    $total = array_sum($prices);
@endphp
```

<a id="including-views"></a>

## Including Views

`@include()` renders another view and hands it all variables of the current
view. Data you pass explicitly wins over inherited variables. `@render()` does
the same but the included view gets **only** the data you pass:

```blade
@include('partials.user', ['user' => $user]) {{-- sees $user and every parent variable --}}
@render('partials.user', ['user' => $user])  {{-- sees only $user --}}
```

`@render_each('view', $items, 'item')` renders a view once per item, like
[View::render_each()](/docs/views#render-each).

<a id="section"></a>
<a id="blade-layout"></a>

## Blade Layout

A layout defines the common page structure with `@yield` placeholders:

```blade
{{-- application/views/master.blade.php --}}
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

A child view names its layout with `@layout()` and fills the sections:

```blade
@layout('master')

@section('content')
    Welcome to the profile page!
@endsection
```

Return the child as usual, `View::make('profile')`, and it is rendered inside
`master`.

> `@layout()` must be on the very first line of the file, with no whitespace
> or newline before it.

`@section` .. `@endsection` (or `@stop`) only defines a section. `@show` closes
it and prints it in place, which is what a layout wants for default content.

<a id="extending-a-section-with-parent"></a>

### Extending a Section with @parent

By default a child's section replaces the layout's. Use `@parent` to keep the
layout's content and add to it:

```blade
@layout('master')

@section('navigation')
    @parent
    <li><a href="contact">Contact</a></li>
@endsection
```

The navigation now has three links: Home, Profile and Contact.

<a id="sections-without-blade"></a>

### Sections Without Blade

Plain PHP views use the `Section` class directly:

```php
<?php Section::start('scripts'); ?>
    <script src="<?php echo asset('js/jquery.js'); ?>"></script>
<?php Section::stop(); ?>

<head><?php echo Section::yield_content('scripts'); ?></head>
```

<a id="stacks"></a>

## Stacks

A stack collects content from several views into one place. Everything pushed
to the same stack is rendered in push order:

```blade
{{-- profile.blade.php --}}
@push('scripts')
    <script src="profile.js"></script>
@endpush

{{-- master.blade.php --}}
<body>
    @yield('content')
    @stack('scripts')
</body>
```

<a id="components"></a>

## Components

A component is reusable markup used with its own tag. Put its view in
`application/views/components/`:

```blade
{{-- application/views/components/alert.blade.php --}}
@props(['type' => 'info'])

<div class="alert alert-{{ $type }}" {{ $attributes }}>
    {{ $slot }}
</div>
```

```html
<x-alert type="error">Data failed to save.</x-alert>
<x-alert type="ok" />
```

Components in subdirectories use a dot:
`application/views/components/forms/input.blade.php` is `<x-forms.input />`.

<a id="attributes"></a>

### Attributes

`@props` lists the attributes the component expects, with their defaults.
Prefix an attribute with `:` to pass a PHP expression instead of a string; an
attribute with no value is `true`:

```html
<x-alert :type="$status" :dismissible="$user->admin">{{ $message }}</x-alert>
<x-forms.input name="email" required />
```

<a id="the-attribute-bag"></a>

### The Attribute Bag

Attributes not named in `@props` stay in `$attributes` and are printed by
`{{ $attributes }}`, so `<x-alert type="error" id="msg-1">` gives
`<div class="alert alert-error" id="msg-1">`.

`merge()` adds defaults underneath the given attributes; `class` values are
joined instead of replaced. `class_names()` builds a class list from
conditions:

```html
<input {{ $attributes->merge(['class' => 'form-control', 'type' => 'text']) }}>
<div {{ $attributes->class_names(['card', 'card-active' => $active]) }}>
```

The bag also has `all()`, `get()`, `has()`, `only()`, `except()` and
`starting_with()`, and can be used as an array.

<a id="slots"></a>

### Slots

Content between the tags arrives as `$slot`. Named slots pass extra pieces:

```blade
{{-- components/card.blade.php --}}
<div class="card">
    <div class="card-title">{{ $title }}</div>
    <div class="card-body">{{ $slot }}</div>
</div>
```

```html
<x-card>
    <x-slot name="title">Monthly Report</x-slot>
    {{-- or: <x-slot:title>Monthly Report</x-slot> --}}
    The report body.
</x-card>
```

Check for an empty slot with `$slot->is_empty()`.

> `{{ $slot }}` prints markup as-is. That is safe: everything inside the slot
> was already escaped once when it was rendered.

<a id="components-with-a-class"></a>

### Components with a Class

A component that needs logic gets a class in `application/components/`, named
with a `_Component` suffix (like controllers use `_Controller`). It extends
`Component`, an alias registered in `application/config/aliases.php`:

```php
// application/components/badge.php
class Badge_Component extends Component
{
    public $label = '';
    public $colour = 'grey';

    public function render()
    {
        return 'components.badge';
        // or return markup directly:
        // return '<span class="badge">' . e($this->label) . '</span>';
    }
}
```

Public properties are passed to the view; a tag attribute with the same name
overrides them. `render()` returns a view name or markup. Register classes that
do not follow the naming in `application/boot.php`:

```php
Component::register('badge', 'My_Own_BadgeComponent');
```

`make:component` generates both the class and its view:

```bash
php rakit make:component alert
```

<a id="components-of-a-package"></a>

### Components of a Package

Package components use the `package::` prefix:

```html
<x-blog::alert type="error">Failed</x-blog::alert>
```

The view is `packages/blog/views/components/alert.blade.php`; an optional
class is `Blog_Alert_Component` in `packages/blog/components/alert.php`.

<a id="other-directives"></a>

## Other Directives

| Directive                              | Description                                                         |
| -------------------------------------- | ------------------------------------------------------------------- |
| `@csrf`                                | Hidden CSRF token field                                             |
| `@method('put')`                       | Hidden `_method` field to spoof the HTTP method (value is escaped)  |
| `@json($data)`                         | Print a value as JSON, safe to embed in `<script>`                  |
| `@auth` .. `@endauth`                  | Shown only to a logged-in user                                      |
| `@guest` .. `@endguest`                | Shown only to a guest                                               |
| `@can('update', $post)` .. `@endcan`   | Shown only when the [ability](/docs/auth/authorization) is granted  |
| `@cannot(..)` / `@canany([..], ..)`    | Its opposite / any of several abilities; end with `@endcannot` / `@endcanany` |
| `@error('field')` .. `@enderror`       | Shown only when the field has a validation error                    |
| `@hassection('name')` .. `@endif`      | Shown only when the section exists                                  |
| `@sectionmissing('name')` .. `@endif`  | Shown only when the section does not exist                          |
| `@once` .. `@endonce`                  | Rendered only once per page, even if the view is included many times |
| `@inject('name', $content)`            | Fill a section from a string, without `@section` .. `@endsection`   |
| `@verbatim` .. `@endverbatim`          | Block that Blade leaves untouched                                   |

```blade
<form method="post" action="{{ url('user/1') }}">
    @csrf
    @method('put')

    <input name="email" value="{{ $user->email }}">
    @error('email')
        <span class="error">{{ $errors->first('email') }}</span>
    @enderror
</form>

<script>
    var user = @json($user);
</script>
```

<a id="controller-layouts"></a>

## Controller Layouts

A controller can set a `$layout` property instead of every action building the
layout. Rakit turns it into a `View` instance, and an action that returns
nothing sends the layout:

```php
class Home_Controller extends Controller
{
    public $layout = 'layouts.master';

    public function action_about()
    {
        $this->layout->title = 'About Us';
        $this->layout->nest('content', 'home.about', ['team' => $team]);
    }
}
```

Use `'name: layout'` to refer to a [named view](/docs/views#named-views).

<a id="extending-blade"></a>

## Extending Blade

`Blade::extend()` registers a custom compiler. It receives the template source
and must return it, typically after a `preg_replace()`:

```php
// application/boot.php
Blade::extend(function ($value) {
    return preg_replace('/@datetime\((.+?)\)/', '<?php echo date("Y-m-d H:i", $1); ?>', $value);
});
```

Compiled views are reused until the template changes. Run
`php rakit clear:views` to clear them.
