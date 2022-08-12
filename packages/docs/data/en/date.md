# Date

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#pengetahuan-dasar)
- [Instantiation](#instansiasi)
- [Addition & Substraction](#tambah--kurang)
- [Difference](#selisih)
- [Comparation](#komparasi)
- [Additional Features](#fitur-tambahan)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Basic Knowledge

The `Date` component is provided to help you when working with time and dates. This component
deliberately made very simple so that you do not have to bother remembering many method names.


<a id="instansiasi"></a>
## Instantiation

There are 2 ways to instantiate this `Date` class, namely via constructor and via static method:

```php
$date = new Date();

$date = Date::make();
```

You can also pass the target date string on instantiation:


```php
$date = new Date('2021-05-23 06:00:00');

$date = Date::make('2021-05-23 06:00:00');
```

In addition to date strings, you can also pass `DateTime` objects, string literals and timestamps:

```php
$date = Date::make(new \DateTime());

$date = Date::make('last sunday');

$date = Date::make(1621987200);
```

> You can enter time format string as per PHP
  [Supported Date and Time Formats](https://www.php.net/manual/en/datetime.formats.php) documentation.


<a id="tambah--kurang"></a>
## Addition & Substraction

Use the `remake()` method to perform addition and subtraction as follows:


#### Addition

```php
$date = '2021-05-23';

return Date::make($date)->remake('+ 3 days'); // 2021-05-26 (added 3 days)
```


#### Substraction

```php
$date = '2021-05-23';

return Date::make($date)->remake('- 3 days'); // 2021-05-20 00:00:00 (substracted 3 days)
```


<a id="selisih"></a>
## Difference

To find the difference between 2 dates, use the `diff()` method as follows:


```php
$diff = Date::diff('2021-05-23', '2020-01-01');

return $diff->days; // 508 days

// dd($diff);

/*
DateInterval Object (
    [y] => 1
    [m] => 4
    [d] => 22
    [h] => 7
    [i] => 38
    [s] => 47
    [f] => 0
    [weekday] => 0
    [weekday_behavior] => 0
    [first_last_day_of] => 0
    [invert] => 0
    [days] => 508
    [special_type] => 0
    [special_amount] => 0
    [have_weekday_relative] => 0
    [have_special_relative] => 0
)
 */
```

You can also ignore the second parameter to find the difference of the first date
with current date:

```php
return Date::diff('2021-05-23');
```


<a id="komparasi"></a>
## Comparation

If you need to compare 2 dates, you can do that too:


#### Equals to

```php
$date = '2021-05-23 00:02:00';

return Date::eq($date, '2021-05-23 00:02:00'); // true
```


#### Greater than

```php
$date = '2021-05-23 00:02:10'; // + 10 secs

return Date::gt($date, '2021-05-23 00:02:00'); // true
```

#### Less than

```php
$date = '2021-05-23 00:01:50'; // - 10 secs

return Date::lt($date, '2021-05-23 00:02:00'); // true
```


#### Greater than or Equals to

```php
$date = '2021-05-23 00:02:10'; // + 10 secs

return Date::gte($date, '2021-05-23 00:02:10'); // true, equals
return Date::gte($date, '2021-05-23 00:02:00'); // true, greater than
```


#### Less than or Equals to

```php
$date = '2021-05-23 00:01:50'; // - 10 detik

return Date::lte($date, '2021-05-23 00:02:00'); // true, less than
return Date::lte($date, '2021-05-23 00:01:50'); // true, equals
```


<a id="fitur-tambahan"></a>
## Additional Features

In addition to the features above, we have also provided additional features that
will certainly make your job easier:



#### Retrieving the timestamp

```php
$date = Date::make('2021-05-23');

return $date->timestamp(); // 1621728000
```


#### Current date time

```php
return Date::make();

// atau,

return mow();
```

If needed, you can also change the time format
according to the format you want, for example like this:


```php
return Date::now('F j, Y H:i');
```


#### Formatting

```php
$date = Date::make('2021-05-23');

return $date->format('F j, Y'); // May 23, 2021
```


#### Time Ago

```php
return Date::make('now - 15 minutes')->ago(); // 15 minute(s) ago

return Date::make('now + 20 minutes')->ago(); // 20 minute(s) from now
```


#### Clone

```php
$date = Date::make('2012-04-05');

$clone = $date->remake('+3 days', true); // clone and +3 days


return $date;  // 2021-05-23 00:00:00
return $clone; // 2021-05-26 00:00:00
```
