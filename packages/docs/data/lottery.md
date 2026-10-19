# Lottery

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Setting the Odds](#setting-the-odds)
-   [Running the Lottery](#running-the-lottery)
-   [Callbacks](#callbacks)
-   [Testing](#testing)
-   [Example](#example)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

`System\Lottery` runs code based on odds. It is handy for feature rollouts, A/B
tests, or anything else that should only happen some of the time. It is also
available as the global `Lottery` alias, so the `use` statement is optional.

```php
use System\Lottery; // optional, the global alias works too

if (Lottery::odds(1, 10)->choose()) {
    // runs roughly 1 in 10 times
}
```

<a id="setting-the-odds"></a>
<a id="creating-instance"></a>

## Setting the Odds

Give odds as "chances out of a total", or as a single float between 0 and 1:

```php
Lottery::odds(1, 10);   // 1 in 10
Lottery::odds(0.25);    // 25%
new Lottery(1, 10);     // same as odds()
```

A float greater than 1 without a total throws an exception.

<a id="running-the-lottery"></a>
<a id="running-lottery"></a>
<a id="direct-invoke"></a>
<a id="multiple-execution"></a>

## Running the Lottery

`choose()` returns `true` on a win and `false` on a loss (or the callback results,
see below). Pass a number to run it several times and get an array of results:

```php
$won = Lottery::odds(1, 2)->choose();       // true or false
$results = Lottery::odds(1, 5)->choose(10); // array of 10 results
```

The instance is also callable. Unlike `choose()`, invoking it forwards its
arguments to the callbacks:

```php
$lottery = Lottery::odds(1, 3)
    ->winner(function ($user, $prize) {
        return "$user won $prize!";
    })
    ->loser(function ($user) {
        return "$user is not lucky yet.";
    });

echo $lottery('John', 'Grand Prize');
```

<a id="callbacks"></a>
<a id="defining-callbacks"></a>
<a id="winner-callback"></a>
<a id="loser-callback"></a>

## Callbacks

`winner()` and `loser()` set the callables to run on a win or a loss. Their return
value becomes the lottery result:

```php
$variant = Lottery::odds(1, 2)
    ->winner(function () {
        return 'variant-a';
    })
    ->loser(function () {
        return 'variant-b';
    })
    ->choose();
```

Without a callback, the result is `true` or `false`.

<a id="testing"></a>
<a id="always-win"></a>
<a id="always-lose"></a>
<a id="sequence"></a>

## Testing

These static methods force the outcome of **every** lottery until you reset it:

| Method                                | Effect                                                         |
| ------------------------------------- | -------------------------------------------------------------- |
| `Lottery::always_win($callback)`      | Always win                                                     |
| `Lottery::always_lose($callback)`     | Always lose                                                    |
| `Lottery::sequence($results, $missing)` | Use the given results in order (alias: `fix()`)              |
| `Lottery::set_factory($callable)`     | Custom generator, receives `($chances, $out_of)`, returns bool |
| `Lottery::normal()`                   | Back to random (alias: `normally()`)                           |

When `always_win()` or `always_lose()` gets a callback, it is run and the lottery is
reset to normal afterwards:

```php
Lottery::always_win(function () {
    // every lottery wins in here
});
```

Once a sequence runs out, results go back to random. Pass a callable as the second
argument to `sequence()` to decide those results instead; it receives
`($chances, $out_of)`:

```php
Lottery::sequence([true, false, true]);

$lottery = Lottery::odds(1, 2);
$lottery->choose(); // true
$lottery->choose(); // false
$lottery->choose(); // true
$lottery->choose(); // random

Lottery::normal();
```

<a id="example"></a>
<a id="usage-examples"></a>
<a id="feature-flags"></a>
<a id="ab-testing"></a>
<a id="rate-limiting"></a>
<a id="random-events"></a>

## Example

Show a new dashboard to about 10% of visitors:

```php
Route::get('dashboard', function () {
    $view = Lottery::odds(10, 100)->choose() ? 'dashboard.new' : 'dashboard.old';

    return View::make($view);
});
```
