# Lottery

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Creating Instance](#creating-instance)
- [Defining Callbacks](#defining-callbacks)
  - [Winner Callback](#winner-callback)
  - [Loser Callback](#loser-callback)
- [Running Lottery](#running-lottery)
- [Direct Invoke](#direct-invoke)
- [Multiple Execution](#multiple-execution)
- [Testing](#testing)
  - [Always Win](#always-win)
  - [Always Lose](#always-lose)
  - [Sequence](#sequence)
- [Usage Examples](#usage-examples)
  - [Feature Flags](#feature-flags)
  - [A/B Testing](#ab-testing)
  - [Rate Limiting](#rate-limiting)
  - [Random Events](#random-events)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The `Lottery` class allows you to run callbacks probabilistically based on specified odds. This class is very useful for implementing feature flags, A/B testing, or other random events in your application.

<a id="creating-instance"></a>
## Creating Instance

You can create a `Lottery` instance in several ways:

```php
use System\Lottery;

// Using probability (1 in 10 chance to win)
$lottery = Lottery::odds(1, 10);

// Using percentage (25% chance to win)
$lottery = Lottery::odds(0.25);

// Or with constructor
$lottery = new Lottery(1, 10);
```

**Note:** If using float, the value must be between 0 and 1 (0 = 0%, 1 = 100%).

<a id="defining-callbacks"></a>
## Defining Callbacks

<a id="winner-callback"></a>
### Winner Callback

Define the callback to run when winning:

```php
$lottery = Lottery::odds(1, 2)
    ->winner(function () {
        return 'Congratulations! You win!';
    });
```

<a id="loser-callback"></a>
### Loser Callback

Define the callback to run when losing:

```php
$lottery = Lottery::odds(1, 2)
    ->winner(function () {
        return 'Congratulations! You win!';
    })
    ->loser(function () {
        return 'Sorry, try again.';
    });
```

<a id="running-lottery"></a>
## Running Lottery

Use the `choose()` method to run the lottery:

```php
$lottery = Lottery::odds(1, 2)
    ->winner(function () {
        return 'Win!';
    })
    ->loser(function () {
        return 'Lose!';
    });

$result = $lottery->choose();
echo $result; // "Win!" or "Lose!" randomly
```

If no callbacks are defined, the method will return `true` for win and `false` for lose:

```php
$lottery = Lottery::odds(1, 10);

if ($lottery->choose()) {
    echo 'Lucky!';
} else {
    echo 'Try again.';
}
```

<a id="direct-invoke"></a>
## Direct Invoke

The `Lottery` class can be invoked directly because it implements the `__invoke()` magic method:

```php
$lottery = Lottery::odds(1, 5)
    ->winner(function () {
        return 'Jackpot!';
    })
    ->loser(function () {
        return 'Next time!';
    });

// Direct invoke
$result = $lottery();
echo $result;
```

You can also pass arguments that will be forwarded to the callbacks:

```php
$lottery = Lottery::odds(1, 3)
    ->winner(function ($user, $prize) {
        return "$user won $prize!";
    })
    ->loser(function ($user) {
        return "$user is not lucky yet.";
    });

$result = $lottery('John', 'Grand Prize');
echo $result;
```

<a id="multiple-execution"></a>
## Multiple Execution

Run the lottery multiple times at once by passing the number of executions to the `choose()` method:

```php
$lottery = Lottery::odds(1, 5)
    ->winner(function () {
        return 'WIN';
    })
    ->loser(function () {
        return 'LOSE';
    });

// Run 10 times
$results = $lottery->choose(10);

// $results is an array containing results from 10 executions
// Example: ['LOSE', 'LOSE', 'WIN', 'LOSE', 'LOSE', 'WIN', 'LOSE', 'LOSE', 'LOSE', 'LOSE']

$win_count = count(array_filter($results, function ($result) {
    return $result === 'WIN';
}));

echo "Won $win_count out of 10 attempts";
```

<a id="testing"></a>
## Testing

For testing purposes, the `Lottery` class provides several methods to control the results:

<a id="always-win"></a>
### Always Win

Force the lottery to always win:

```php
// For testing
Lottery::always_win();

$lottery = Lottery::odds(1, 1000);
$result = $lottery->choose();
// Always returns true or runs winner callback

// Reset to normal
Lottery::normal();
```

With callback:

```php
Lottery::always_win(function () {
    // Run test
    $lottery = Lottery::odds(1, 100);
    $result = $lottery->choose();

    // Assert result is true/winner
});

// Automatically reset to normal after callback finishes
```

<a id="always-lose"></a>
### Always Lose

Force the lottery to always lose:

```php
// For testing
Lottery::always_lose();

$lottery = Lottery::odds(999, 1000);
$result = $lottery->choose();
// Always returns false or runs loser callback

// Reset to normal
Lottery::normal();
```

With callback:

```php
Lottery::always_lose(function () {
    // Run test
    $lottery = Lottery::odds(999, 1000);
    $result = $lottery->choose();

    // Assert result is false/loser
});

// Automatically reset to normal after callback finishes
```

<a id="sequence"></a>
### Sequence

Specify a sequence of results for more complex testing:

```php
// Specify sequence: win, lose, lose, win
Lottery::sequence([true, false, false, true]);

$lottery = Lottery::odds(1, 2);

$lottery->choose(); // true (win)
$lottery->choose(); // false (lose)
$lottery->choose(); // false (lose)
$lottery->choose(); // true (win)
$lottery->choose(); // Random (sequence exhausted, back to random)

// Reset to normal
Lottery::normal();
```

Alias `fix()` is also available:

```php
Lottery::fix([true, false, true]);
```

<a id="usage-examples"></a>
## Usage Examples

<a id="feature-flags"></a>
### Feature Flags

Enable new features for a certain percentage of users:

```php
// Enable new feature for 10% of users
$enable_new_feature = Lottery::odds(10, 100)
    ->winner(function () {
        return true;
    })
    ->loser(function () {
        return false;
    })
    ->choose();

if ($enable_new_feature) {
    // Show new feature
    return View::make('dashboard.new');
} else {
    // Show old feature
    return View::make('dashboard.old');
}
```

<a id="ab-testing"></a>
### A/B Testing

Implement A/B testing with 50/50 odds:

```php
Route::get('/', function () {
    $variant = Lottery::odds(1, 2)
        ->winner(function () {
            return 'variant-a';
        })
        ->loser(function () {
            return 'variant-b';
        })
        ->choose();

    // Save variant to session
    Session::put('ab_test_variant', $variant);

    return View::make('home.' . $variant);
});
```

<a id="rate-limiting"></a>
### Rate Limiting

Implement probabilistic rate limiting:

```php
// Throttle 20% of requests
$should_throttle = Lottery::odds(20, 100)->choose();

if ($should_throttle) {
    return Response::make('Too many requests', 429);
}

// Process normal request
```

<a id="random-events"></a>
### Random Events

Trigger random events in games or applications:

```php
// 5% chance to get bonus
$got_bonus = Lottery::odds(5, 100)
    ->winner(function () {
        $bonus_amount = rand(10, 100);

        // Give bonus to user
        Auth::user()->add_credits($bonus_amount);

        return "Congratulations! You got a bonus of $bonus_amount credits!";
    })
    ->loser(function () {
        return null;
    })
    ->choose();

if ($got_bonus) {
    Session::flash('message', $got_bonus);
}
```

With multiple outcomes:

```php
// Random reward system
$reward_type = Lottery::odds(1, 10)
    ->winner(function () {
        // 10% chance - rare reward
        return 'rare';
    })
    ->loser(function () {
        // 90% chance - choose between common or uncommon
        return Lottery::odds(7, 10)
            ->winner(function () {
                return 'common';
            })
            ->loser(function () {
                return 'uncommon';
            })
            ->choose();
    })
    ->choose();

switch ($reward_type) {
    case 'rare':
        $reward = 'Diamond Sword';
        break;
    case 'uncommon':
        $reward = 'Iron Armor';
        break;
    default:
        $reward = 'Wooden Shield';
}
```

Random maintenance mode:

```php
// Redirect 10% traffic to maintenance page for testing
Route::filter('before', function () {
    $in_maintenance = Lottery::odds(1, 10)->choose();

    if ($in_maintenance && !Auth::user()->is_admin) {
        return Response::error('503');
    }
});
```
