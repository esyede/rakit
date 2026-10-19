# Carbon

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Creating Instances](#creating-instances)
-   [Getters and Setters](#getters-and-setters)
    -   [Properties](#properties)
    -   [Setter Methods](#setter-methods)
-   [Formatting](#formatting)
-   [Comparison](#comparison)
-   [Addition and Subtraction](#addition-and-subtraction)
-   [Differences](#differences)
    -   [Human Readable Differences](#human-readable-differences)
-   [Modifiers](#modifiers)
-   [Testing](#testing)
-   [Example](#example)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

`Carbon` extends PHP's `DateTime` with fluent methods for creating, manipulating,
comparing and formatting dates:

```php
echo Carbon::now()->addDays(3)->toDateString(); // e.g. 2023-01-04
```

When no timezone is given, the `application.timezone` config value is used.
Timezones can be passed as a name (`'Asia/Jakarta'`) or a `DateTimeZone` object.

> Carbon instances are **mutable**: methods such as `addDay()` or `startOfDay()`
> change the instance itself. Call `copy()` first when you need to keep the original.

<a id="creating-instances"></a>
<a id="creating-carbon-instances"></a>
<a id="method-reference"></a>
<a id="__construct"></a>
<a id="instance"></a>
<a id="parse"></a>
<a id="now"></a>
<a id="today"></a>
<a id="tomorrow"></a>
<a id="yesterday"></a>
<a id="maxvalue"></a>
<a id="minvalue"></a>
<a id="create"></a>
<a id="createfromdate"></a>
<a id="createfromtime"></a>
<a id="createfromformat"></a>
<a id="createfromtimestamp"></a>
<a id="createfromtimestamputc"></a>
<a id="copy"></a>

## Creating Instances

```php
new Carbon();                          // now
new Carbon('2023-01-01 12:00:00', 'UTC');
Carbon::parse('2023-01-01');           // same as new Carbon($time, $tz)
Carbon::instance(new DateTime());      // from a DateTime

Carbon::now();                         // current date and time
Carbon::today();                       // today, 00:00:00
Carbon::tomorrow();                    // tomorrow, 00:00:00
Carbon::yesterday();                   // yesterday, 00:00:00

Carbon::create(2023, 1, 1, 12, 0, 0);  // year, month, day, hour, minute, second, tz
Carbon::createFromDate(2023, 1, 1);    // time is the current time
Carbon::createFromTime(12, 0, 0);      // date is today
Carbon::createFromFormat('Y-m-d', '2023-01-01');
Carbon::createFromTimestamp(1672531200);
Carbon::createFromTimestampUTC(1672531200);

Carbon::maxValue();                    // largest possible date
Carbon::minValue();                    // smallest possible date

$copy = $date->copy();                 // independent clone
```

`now()`, `today()`, `tomorrow()`, `yesterday()`, `parse()` and all `create*()`
methods accept an optional timezone as their last argument (except
`createFromTimestampUTC()`). In `create()`, any `null` date part defaults to
today; a `null` hour uses the current time, while a `null` minute or second
after a given hour defaults to `0`. `createFromFormat()` throws an exception when
the string does not match the format.

<a id="getters-and-setters"></a>

## Getters and Setters

<a id="properties"></a>
<a id="__get"></a>
<a id="__isset"></a>
<a id="__set"></a>

### Properties

| Property                      | Returns                                         | Settable |
| ----------------------------- | ----------------------------------------------- | -------- |
| `year`, `month`, `day`        | int                                             | yes      |
| `hour`, `minute`, `second`    | int                                             | yes      |
| `micro`                       | int, microseconds                               |          |
| `timestamp`                   | int, Unix timestamp                             | yes      |
| `timezone`, `tz`              | `DateTimeZone` (set with a name or object)      | yes      |
| `timezoneName`, `tzName`      | string, e.g. `Asia/Jakarta`                     |          |
| `dayOfWeek`                   | int, `0` (Sunday) to `6` (Saturday)             |          |
| `dayOfYear`                   | int, starting at `0`                            |          |
| `weekOfYear`, `yearIso`       | int, ISO-8601 week number and year              |          |
| `weekOfMonth`                 | int, `1` to `5`                                 |          |
| `daysInMonth`                 | int                                             |          |
| `quarter`                     | int, `1` to `4`                                 |          |
| `age`                         | int, years until now                            |          |
| `offset`, `offsetHours`       | int, UTC offset in seconds / hours              |          |
| `dst`                         | bool, daylight saving time in effect            |          |
| `utc`                         | bool, offset is zero                            |          |

```php
$date->year;          // 2023
$date->month = 6;     // set the month
isset($date->tzName); // true for any known property
```

Reading or setting an unknown property throws an exception. Use the
`Carbon::SUNDAY` … `Carbon::SATURDAY` constants (`0` to `6`) to compare
`dayOfWeek`.

<a id="setter-methods"></a>
<a id="year"></a>
<a id="month"></a>
<a id="day"></a>
<a id="hour"></a>
<a id="minute"></a>
<a id="second"></a>
<a id="setdatetime"></a>
<a id="timestamp"></a>
<a id="timezone"></a>
<a id="tz"></a>
<a id="settimezone"></a>

### Setter Methods

Chainable equivalents of the settable properties:

```php
$date->year(2023)->month(1)->day(1)->hour(12)->minute(0)->second(0);
$date->setDateTime(2023, 1, 1, 12, 0, 0); // second is optional
$date->timestamp(1672531200);
$date->timezone('UTC');                   // aliases: tz(), setTimezone()
```

<a id="formatting"></a>
<a id="resettostringformat"></a>
<a id="settostringformat"></a>
<a id="__tostring"></a>
<a id="todatestring"></a>
<a id="toformatteddatestring"></a>
<a id="totimestring"></a>
<a id="todatetimestring"></a>
<a id="todaydatetimestring"></a>
<a id="toatomstring"></a>
<a id="tocookiestring"></a>
<a id="toiso8601string"></a>
<a id="torfc822string"></a>
<a id="torfc850string"></a>
<a id="torfc1036string"></a>
<a id="torfc1123string"></a>
<a id="torfc2822string"></a>
<a id="torfc3339string"></a>
<a id="torssstring"></a>
<a id="tow3cstring"></a>

## Formatting

Besides the inherited `format()`, these return common formats (examples for
`2023-01-01 12:00:00 UTC`):

| Method                    | Example                              |
| ------------------------- | ------------------------------------ |
| `toDateString()`          | `2023-01-01`                         |
| `toFormattedDateString()` | `Jan 1, 2023`                        |
| `toTimeString()`          | `12:00:00`                           |
| `toDateTimeString()`      | `2023-01-01 12:00:00`                |
| `toDayDateTimeString()`   | `Sun, Jan 1, 2023 12:00 PM`          |
| `toAtomString()`          | `2023-01-01T12:00:00+00:00`          |
| `toCookieString()`        | `Sunday, 01-Jan-2023 12:00:00 UTC`   |
| `toIso8601String()`       | `2023-01-01T12:00:00+00:00`          |
| `toRfc822String()`        | `Sun, 01 Jan 23 12:00:00 +0000`      |
| `toRfc850String()`        | `Sunday, 01-Jan-23 12:00:00 UTC`     |
| `toRfc1036String()`       | `Sun, 01 Jan 23 12:00:00 +0000`      |
| `toRfc1123String()`       | `Sun, 01 Jan 2023 12:00:00 +0000`    |
| `toRfc2822String()`       | `Sun, 01 Jan 2023 12:00:00 +0000`    |
| `toRfc3339String()`       | `2023-01-01T12:00:00+00:00`          |
| `toRssString()`           | `Sun, 01 Jan 2023 12:00:00 +0000`    |
| `toW3cString()`           | `2023-01-01T12:00:00+00:00`          |

Casting to a string uses `Y-m-d H:i:s`, which you can change globally:

```php
echo $date;                        // 2023-01-01 12:00:00

Carbon::setToStringFormat('d/m/Y');
echo $date;                        // 01/01/2023

Carbon::resetToStringFormat();
```

<a id="comparison"></a>
<a id="eq"></a>
<a id="ne"></a>
<a id="gt"></a>
<a id="gte"></a>
<a id="lt"></a>
<a id="lte"></a>
<a id="between"></a>
<a id="min"></a>
<a id="max"></a>
<a id="isweekday"></a>
<a id="isweekend"></a>
<a id="isyesterday"></a>
<a id="istoday"></a>
<a id="istomorrow"></a>
<a id="isfuture"></a>
<a id="ispast"></a>
<a id="isleapyear"></a>
<a id="issameday"></a>
<a id="isbirthday"></a>

## Comparison

These take another `Carbon` instance:

```php
$a->eq($b);   // equal
$a->ne($b);   // not equal
$a->gt($b);   // greater than
$a->gte($b);  // greater than or equal
$a->lt($b);   // less than
$a->lte($b);  // less than or equal

$date->between($start, $end);        // inclusive; argument order does not matter
$date->between($start, $end, false); // exclusive

$a->min($b);  // the earlier of the two (default: now)
$a->max($b);  // the later of the two (default: now)

$a->isSameDay($b);   // same calendar date
$a->isBirthday($b);  // same month and day
```

And these check the instance itself:

| Method          | True when                       |
| --------------- | ------------------------------- |
| `isWeekday()`   | Monday to Friday                |
| `isWeekend()`   | Saturday or Sunday              |
| `isYesterday()` | the date is yesterday           |
| `isToday()`     | the date is today               |
| `isTomorrow()`  | the date is tomorrow            |
| `isFuture()`    | later than now                  |
| `isPast()`      | earlier than now                |
| `isLeapYear()`  | the year is a leap year         |

<a id="addition-and-subtraction"></a>
<a id="addyears"></a>
<a id="addyear"></a>
<a id="subyear"></a>
<a id="subyears"></a>
<a id="addmonths"></a>
<a id="addmonth"></a>
<a id="submonth"></a>
<a id="submonths"></a>
<a id="addmonthsnooverflow"></a>
<a id="addmonthnooverflow"></a>
<a id="submonthnooverflow"></a>
<a id="submonthsnooverflow"></a>
<a id="adddays"></a>
<a id="addday"></a>
<a id="subday"></a>
<a id="subdays"></a>
<a id="addweekdays"></a>
<a id="addweekday"></a>
<a id="subweekday"></a>
<a id="subweekdays"></a>
<a id="addweeks"></a>
<a id="addweek"></a>
<a id="subweek"></a>
<a id="subweeks"></a>
<a id="addhours"></a>
<a id="addhour"></a>
<a id="subhour"></a>
<a id="subhours"></a>
<a id="addminutes"></a>
<a id="addminute"></a>
<a id="subminute"></a>
<a id="subminutes"></a>
<a id="addseconds"></a>
<a id="addsecond"></a>
<a id="subsecond"></a>
<a id="subseconds"></a>

## Addition and Subtraction

Every unit has four methods: `addXs($n)`, `addX()`, `subXs($n)` and `subX()`.
They change the instance and return it:

| Unit     | Methods                                                         |
| -------- | --------------------------------------------------------------- |
| Years    | `addYears($n)`, `addYear()`, `subYears($n)`, `subYear()`        |
| Months   | `addMonths($n)`, `addMonth()`, `subMonths($n)`, `subMonth()`    |
| Weeks    | `addWeeks($n)`, `addWeek()`, `subWeeks($n)`, `subWeek()`        |
| Days     | `addDays($n)`, `addDay()`, `subDays($n)`, `subDay()`            |
| Weekdays | `addWeekdays($n)`, `addWeekday()`, `subWeekdays($n)`, `subWeekday()` |
| Hours    | `addHours($n)`, `addHour()`, `subHours($n)`, `subHour()`        |
| Minutes  | `addMinutes($n)`, `addMinute()`, `subMinutes($n)`, `subMinute()` |
| Seconds  | `addSeconds($n)`, `addSecond()`, `subSeconds($n)`, `subSecond()` |

```php
$date = Carbon::create(2023, 1, 31, 0, 0, 0);

$date->addDays(5)->subHours(2);
$date->addWeekdays(3);  // skips Saturday and Sunday
```

Adding months to the 31st can overflow into the following month (Jan 31 + 1 month
= Mar 3). The `NoOverflow` variants clamp to the last day of the month instead:
`addMonthsNoOverflow($n)`, `addMonthNoOverflow()`, `subMonthsNoOverflow($n)`,
`subMonthNoOverflow()`.

```php
$jan = Carbon::create(2023, 1, 31, 0, 0, 0);
$feb = $jan->addMonthNoOverflow(); // 2023-02-28
```

> Unlike the other methods, the `NoOverflow` variants return a **new** instance and
> leave the original unchanged.

<a id="differences"></a>
<a id="diffinyears"></a>
<a id="diffinmonths"></a>
<a id="diffinweeks"></a>
<a id="diffindays"></a>
<a id="diffindaysfiltered"></a>
<a id="diffinweekdays"></a>
<a id="diffinweekenddays"></a>
<a id="diffinhours"></a>
<a id="diffinminutes"></a>
<a id="diffinseconds"></a>
<a id="secondssincemidnight"></a>
<a id="secondsuntilendofday"></a>

## Differences

Each `diffIn*()` method takes another date (default: now) and returns a whole
number. The result is absolute unless you pass `false` as the last argument, in
which case it is negative when the other date is earlier:

```php
$a->diffInYears($b);
$a->diffInMonths($b);
$a->diffInWeeks($b);
$a->diffInDays($b);
$a->diffInHours($b);
$a->diffInMinutes($b);
$a->diffInSeconds($b, false);  // signed

$a->diffInWeekdays($b);        // Monday to Friday only
$a->diffInWeekendDays($b);     // Saturday and Sunday only

// Count only the days the callback accepts
$a->diffInDaysFiltered(function ($date) {
    return $date->day === 1;
}, $b);

$date->secondsSinceMidnight();
$date->secondsUntilEndOfDay();
```

<a id="human-readable-differences"></a>
<a id="diffforhumans"></a>

### Human Readable Differences

`diffForHumans()` describes the difference in words, rounded to the largest unit:

```php
Carbon::now()->subDays(3)->diffForHumans();   // 3 days ago
Carbon::now()->addHour()->diffForHumans();    // 1 hour from now
$a->diffForHumans($b);                        // 2 weeks before / 2 weeks after
$a->diffForHumans($b, true);                  // 2 weeks
```

Without an argument the date is compared to now ("ago" / "from now"); with one,
it reads "before" / "after". Passing `true` as the second argument drops the
suffix. The words come from `application/language/<lang>/carbon.php`, so they
follow the application language.

<a id="modifiers"></a>
<a id="startofday"></a>
<a id="endofday"></a>
<a id="startofmonth"></a>
<a id="endofmonth"></a>
<a id="startofyear"></a>
<a id="endofyear"></a>
<a id="startofdecade"></a>
<a id="endofdecade"></a>
<a id="startofcentury"></a>
<a id="endofcentury"></a>
<a id="startofweek"></a>
<a id="endofweek"></a>
<a id="next"></a>
<a id="previous"></a>
<a id="firstofmonth"></a>
<a id="lastofmonth"></a>
<a id="nthofmonth"></a>
<a id="firstofquarter"></a>
<a id="lastofquarter"></a>
<a id="nthofquarter"></a>
<a id="firstofyear"></a>
<a id="lastofyear"></a>
<a id="nthofyear"></a>
<a id="average"></a>

## Modifiers

Move the instance to the start or end of a period. `startOf*()` sets the time to
`00:00:00`, `endOf*()` to `23:59:59`:

```php
$date->startOfDay();      $date->endOfDay();
$date->startOfWeek();     $date->endOfWeek();     // Monday / Sunday
$date->startOfMonth();    $date->endOfMonth();
$date->startOfYear();     $date->endOfYear();
$date->startOfDecade();   $date->endOfDecade();
$date->startOfCentury();  $date->endOfCentury();
```

Jump to a day of the week. These also reset the time to `00:00:00`:

```php
$date->next(Carbon::MONDAY);      // next Monday (default: same weekday next week)
$date->previous(Carbon::FRIDAY);  // last Friday (default: same weekday last week)

$date->firstOfMonth();               // 1st of the month
$date->firstOfMonth(Carbon::MONDAY); // first Monday of the month
$date->lastOfMonth(Carbon::FRIDAY);  // last Friday of the month
$date->nthOfMonth(2, Carbon::MONDAY); // second Monday of the month

$date->firstOfQuarter();  $date->lastOfQuarter();  $date->nthOfQuarter(2, Carbon::MONDAY);
$date->firstOfYear();     $date->lastOfYear();     $date->nthOfYear(2, Carbon::MONDAY);
```

The `firstOf*()` and `lastOf*()` methods take an optional day of the week. The
`nthOf*()` methods return `false` (and leave the date unchanged) when that
occurrence does not exist in the period.

`average()` moves the instance to the midpoint between it and another date
(default: now):

```php
$mid = Carbon::create(2023, 1, 1, 0, 0, 0)->average(Carbon::create(2023, 1, 3, 0, 0, 0));
// 2023-01-02 00:00:00
```

<a id="testing"></a>
<a id="setnow"></a>
<a id="gettestnow"></a>
<a id="hastestnow"></a>
<a id="hasrelativekeywords"></a>

## Testing

`setNow()` fixes the current time. Afterwards, `now()`, `new Carbon()` and relative
strings such as `'tomorrow'` or `'+1 day'` are based on that time:

```php
Carbon::setNow(Carbon::create(2023, 1, 1, 12, 0, 0));

echo Carbon::now();               // 2023-01-01 12:00:00
echo new Carbon('tomorrow');      // 2023-01-02 00:00:00

Carbon::hasTestNow();             // true
Carbon::getTestNow();             // the fixed instance

Carbon::setNow();                 // back to the real time
```

`Carbon::hasRelativeKeywords($time)` tells whether a string is relative (contains
words such as `next`, `last`, `ago`, `+` or `-`, and no `Y-m-d` date).

<a id="example"></a>

## Example

Show when a subscription expires:

```php
$expires = Carbon::parse($user->subscribed_at)->addMonthsNoOverflow(1)->endOfDay();

if ($expires->isPast()) {
    echo 'Expired '.$expires->diffForHumans();
} else {
    echo 'Expires on '.$expires->toFormattedDateString()
        .' ('.$expires->diffInDays().' days left)';
}
```
