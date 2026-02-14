# Templating

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Section](#section)
-   [Blade Template Engine](#blade-template-engine)
-   [Blade Conditionals & Looping](#blade-conditionals--looping)
-   [Blade Layout](#blade-layout)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Your application may use a common layout on most of its pages.
Repeatedly creating this layout manually in every action in the controller would certainly be quite annoying.

Defining a layout for a controller will make your development much more enjoyable. Here's how:

#### Add the `$layout` property to your controller:

```php
class Home_Controller extends Controller
{
	public $layout = 'layouts.common';

	// ..
}
```

> After the `$layout` property is filled, Rakit will intelligently convert it into an instance of the `View` class.

#### Access its layout from the action in the controller:

```php
public function action_profile()
{
	$this->layout->nest('content', 'user.profile');
}
```

> When your controller uses this layout feature, the action does not need to return anything to display the view.

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

View sections (or parts of the view) provide a simple way to insert content into the layout from nested views.
For example, you might want to insert the JavaScript required by the nested view into your layout header. Here's how:

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

Blade makes writing your views even more enjoyable. To create a view using Blade, just use the `.blade.php` extension on your view file.

Blade allows you to use simpler and more elegant syntax for writing and displaying data.

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

Since many JavaScript frameworks also use curly braces to indicate that the given syntax should be displayed in the browser,
you can use the `@` symbol to tell the Blade rendering engine to ignore this syntax. For example:

```blade
Hello, @{{ $name }}.
```

In the above example, the `@` symbol will be removed by Blade; however,
the `{{ name }}` syntax will remain untouched by the Blade engine, so this syntax can be rendered by your JavaScript framework.

#### Displaying data with default value

Sometimes you may want to display a variable, but you are not sure if the variable has been defined or not.
Indeed, you can write it verbosely like:

```blade
{{ isset($name) ? $name : 'Guest' }}
```

However, instead of writing using the ternary operator like above, Blade gives you an easier shortcut:

```blade
{{ $name or 'Guest' }}
```

In the above example, if the `$name` variable exists, its value will be displayed.
However, if not, the word `Guest` will be displayed.

#### Displaying data without escaping

By default, data enclosed by the `{{ }}` syntax will be automatically escaped
using the [htmlentities](https://www.php.net/manual/en/function.htmlentities.php) function to prevent XSS attacks.

If you do not want your data to be escaped, you can use the following syntax:

```blade
Hello, {!! $name !!}
```

> Be careful when displaying data from user input. Always use the `{{` `}}` syntax to avoid any HTML entities in the data.

#### Displaying a view:

Use the `@include()` syntax to import a view into another view.
The imported view will automatically inherit all data from the current view.

```blade
<h1>Profile</h1>

@include('user.profile')
```

You can also use `@render()`, which behaves almost the same as `@include()` except that the
rendered view **does not inherit** data from the current view.

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

Blade not only provides clean and elegant syntax for common PHP control structures, but also gives you a beautiful method for using layouts for your views.

**Template inheritance** allows you to create a master layout and extend that layout in other views.

For example, if your application uses the `'master'` view to provide a consistent look for your application. An example like this:

**File: `application/views/master.blade.php`**

```blade
<html>
	<ul class="navigation">
		@section('navigation')
			<li><a href="home">Home</a></li>
			<li><a href="profile">Profile</a></li>
		@endsection
	</ul>

	<div class="content">
		@yield('content')
	</div>
</html>
```

Notice the `'content'` section that is yielded. We need to fill this section with some text, so let's create another view that uses this one:

**File: `application/views/profile.blade.php`**

```blade
@layout('master')

@section('content')
	Welcome to the profile page!
@endsection
```

Great! Now, we just need to return the `profile` view from our route:

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

Sometimes you may only want to add something to a layout section rather than overriding it.
For example, consider the navigation list in the `master` layout [above](#blade-layout).

Suppose we just want to add a `Contact` link to that navigation list. Here's how:

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

The `@parent` tag will be replaced with the content of the navigation section of the layout,
so you are more free to extend and inherit the layout.

**Final result:**

When the `profile` view is rendered, the `navigation` section will contain:

```html
<li><a href="home">Home</a></li>
<li><a href="profile">Profile</a></li>
<li><a href="contact">Contact</a></li>
```

Without `@parent`, the navigation section would be completely replaced with the new content, not added.
