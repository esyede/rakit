# Carbon

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Creating Carbon Instances](#creating-carbon-instances)
- [Method Reference](#method-reference)
  - [__construct()](#__construct)
  - [instance()](#instance)
  - [parse()](#parse)
  - [now()](#now)
  - [today()](#today)
  - [tomorrow()](#tomorrow)
  - [yesterday()](#yesterday)
  - [maxValue()](#maxvalue)
  - [minValue()](#minvalue)
  - [create()](#create)
  - [createFromDate()](#createfromdate)
  - [createFromTime()](#createfromtime)
  - [createFromFormat()](#createfromformat)
  - [createFromTimestamp()](#createfromtimestamp)
  - [createFromTimestampUTC()](#createfromtimestamputc)
  - [copy()](#copy)
  - [__get()](#__get)
  - [__isset()](#__isset)
  - [__set()](#__set)
  - [year()](#year)
  - [month()](#month)
  - [day()](#day)
  - [hour()](#hour)
  - [minute()](#minute)
  - [second()](#second)
  - [setDateTime()](#setdatetime)
  - [timestamp()](#timestamp)
  - [timezone()](#timezone)
  - [tz()](#tz)
  - [setTimezone()](#settimezone)
  - [setNow()](#setnow)
  - [getTestNow()](#gettestnow)
  - [hasTestNow()](#hastestnow)
  - [hasRelativeKeywords()](#hasrelativekeywords)
  - [resetToStringFormat()](#resettostringformat)
  - [setToStringFormat()](#settostringformat)
  - [__toString()](#__tostring)
  - [toDateString()](#todatestring)
  - [toFormattedDateString()](#toformatteddatestring)
  - [toTimeString()](#totimestring)
  - [toDateTimeString()](#todatetimestring)
  - [toDayDateTimeString()](#todaydatetimestring)
  - [toAtomString()](#toatomstring)
  - [toCookieString()](#tocookiestring)
  - [toIso8601String()](#toiso8601string)
  - [toRfc822String()](#torfc822string)
  - [toRfc850String()](#torfc850string)
  - [toRfc1036String()](#torfc1036string)
  - [toRfc1123String()](#torfc1123string)
  - [toRfc2822String()](#torfc2822string)
  - [toRfc3339String()](#torfc3339string)
  - [toRssString()](#torssstring)
  - [toW3cString()](#tow3cstring)
  - [eq()](#eq)
  - [ne()](#ne)
  - [gt()](#gt)
  - [gte()](#gte)
  - [lt()](#lt)
  - [lte()](#lte)
  - [between()](#between)
  - [min()](#min)
  - [max()](#max)
  - [isWeekday()](#isweekday)
  - [isWeekend()](#isweekend)
  - [isYesterday()](#isyesterday)
  - [isToday()](#istoday)
  - [isTomorrow()](#istomorrow)
  - [isFuture()](#isfuture)
  - [isPast()](#ispast)
  - [isLeapYear()](#isleapyear)
  - [isSameDay()](#issameday)
  - [addYears()](#addyears)
  - [addYear()](#addyear)
  - [subYear()](#subyear)
  - [subYears()](#subyears)
  - [addMonths()](#addmonths)
  - [addMonth()](#addmonth)
  - [subMonth()](#submonth)
  - [subMonths()](#submonths)
  - [addMonthsNoOverflow()](#addmonthsnooverflow)
  - [addMonthNoOverflow()](#addmonthnooverflow)
  - [subMonthNoOverflow()](#submonthnooverflow)
  - [subMonthsNoOverflow()](#submonthsnooverflow)
  - [addDays()](#adddays)
  - [addDay()](#addday)
  - [subDay()](#subday)
  - [subDays()](#subdays)
  - [addWeekdays()](#addweekdays)
  - [addWeekday()](#addweekday)
  - [subWeekday()](#subweekday)
  - [subWeekdays()](#subweekdays)
  - [addWeeks()](#addweeks)
  - [addWeek()](#addweek)
  - [subWeek()](#subweek)
  - [subWeeks()](#subweeks)
  - [addHours()](#addhours)
  - [addHour()](#addhour)
  - [subHour()](#subhour)
  - [subHours()](#subhours)
  - [addMinutes()](#addminutes)
  - [addMinute()](#addminute)
  - [subMinute()](#subminute)
  - [subMinutes()](#subminutes)
  - [addSeconds()](#addseconds)
  - [addSecond()](#addsecond)
  - [subSecond()](#subsecond)
  - [subSeconds()](#subseconds)
  - [diffInYears()](#diffinyears)
  - [diffInMonths()](#diffinmonths)
  - [diffInWeeks()](#diffinweeks)
  - [diffInDays()](#diffindays)
  - [diffInDaysFiltered()](#diffindaysfiltered)
  - [diffInWeekdays()](#diffinweekdays)
  - [diffInWeekendDays()](#diffinweekenddays)
  - [diffInHours()](#diffinhours)
  - [diffInMinutes()](#diffinminutes)
  - [diffInSeconds()](#diffinseconds)
  - [secondsSinceMidnight()](#secondssincemidnight)
  - [secondsUntilEndOfDay()](#secondsuntilendofday)
  - [diffForHumans()](#diffforhumans)
  - [startOfDay()](#startofday)
  - [endOfDay()](#endofday)
  - [startOfMonth()](#startofmonth)
  - [endOfMonth()](#endofmonth)
  - [startOfYear()](#startofyear)
  - [endOfYear()](#endofyear)
  - [startOfDecade()](#startofdecade)
  - [endOfDecade()](#endofdecade)
  - [startOfCentury()](#startofcentury)
  - [endOfCentury()](#endofcentury)
  - [startOfWeek()](#startofweek)
  - [endOfWeek()](#endofweek)
  - [next()](#next)
  - [previous()](#previous)
  - [firstOfMonth()](#firstofmonth)
  - [lastOfMonth()](#lastofmonth)
  - [nthOfMonth()](#nthofmonth)
  - [firstOfQuarter()](#firstofquarter)
  - [lastOfQuarter()](#lastofquarter)
  - [nthOfQuarter()](#nthofquarter)
  - [firstOfYear()](#firstofyear)
  - [lastOfYear()](#lastofyear)
  - [nthOfYear()](#nthofyear)
  - [average()](#average)
  - [isBirthday()](#isbirthday)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Carbon is a wrapper for PHP's DateTime class that provides various methods to work with date and time data more easily and expressively. It extends DateTime and adds fluent methods for date manipulation, comparisons, and formatting.

<a id="creating-carbon-instances"></a>

## Creating Carbon Instances

Carbon instances can be created in various ways:

#### From current time:

```php
$carbon = new Carbon(); // Current date and time
```

#### From specific time:

```php
$carbon = new Carbon('2023-01-01 12:00:00');
```

#### Using static methods:

```php
$now = Carbon::now();
$today = Carbon::today();
$tomorrow = Carbon::tomorrow();
$yesterday = Carbon::yesterday();
```

#### From timestamp:

```php
$carbon = Carbon::createFromTimestamp(1609459200);
```

#### From format:

```php
$carbon = Carbon::createFromFormat('Y-m-d', '2023-01-01');
```

<a id="method-reference"></a>

## Method Reference

<a id="__construct"></a>

### __construct()

Creates a new Carbon instance.

```php
$carbon = new Carbon('2023-01-01 12:00:00');
```

<a id="instance"></a>

### instance()

Creates a Carbon instance from a DateTime object.

```php
$dt = new DateTime('2023-01-01');
$carbon = Carbon::instance($dt);
```

<a id="parse"></a>

### parse()

Parses a date string and creates a Carbon instance.

```php
$carbon = Carbon::parse('2023-01-01');
```

<a id="now"></a>

### now()

Creates a Carbon instance for the current date and time.

```php
$carbon = Carbon::now();
```

<a id="today"></a>

### today()

Creates a Carbon instance for the start of today.

```php
$carbon = Carbon::today();
```

<a id="tomorrow"></a>

### tomorrow()

Creates a Carbon instance for the start of tomorrow.

```php
$carbon = Carbon::tomorrow();
```

<a id="yesterday"></a>

### yesterday()

Creates a Carbon instance for the start of yesterday.

```php
$carbon = Carbon::yesterday();
```

<a id="maxvalue"></a>

### maxValue()

Returns the maximum possible Carbon value.

```php
$max = Carbon::maxValue();
```

<a id="minvalue"></a>

### minValue()

Returns the minimum possible Carbon value.

```php
$min = Carbon::minValue();
```

<a id="create"></a>

### create()

Creates a Carbon instance with specified year, month, day, hour, minute, second.

```php
$carbon = Carbon::create(2023, 1, 1, 12, 0, 0);
```

<a id="createfromdate"></a>

### createFromDate()

Creates a Carbon instance from date components.

```php
$carbon = Carbon::createFromDate(2023, 1, 1);
```

<a id="createfromtime"></a>

### createFromTime()

Creates a Carbon instance from time components.

```php
$carbon = Carbon::createFromTime(12, 0, 0);
```

<a id="createfromformat"></a>

### createFromFormat()

Creates a Carbon instance from a formatted string.

```php
$carbon = Carbon::createFromFormat('Y-m-d', '2023-01-01');
```

<a id="createfromtimestamp"></a>

### createFromTimestamp()

Creates a Carbon instance from a Unix timestamp.

```php
$carbon = Carbon::createFromTimestamp(1609459200);
```

<a id="createfromtimestamputc"></a>

### createFromTimestampUTC()

Creates a Carbon instance from a UTC Unix timestamp.

```php
$carbon = Carbon::createFromTimestampUTC(1609459200);
```

<a id="copy"></a>

### copy()

Creates a copy of the Carbon instance.

```php
$copy = $carbon->copy();
```

<a id="__get"></a>

### __get()

Gets various date/time properties.

```php
$year = $carbon->year;
$month = $carbon->month;
$day = $carbon->day;
$hour = $carbon->hour;
$minute = $carbon->minute;
$second = $carbon->second;
$dayOfWeek = $carbon->dayOfWeek;
$dayOfYear = $carbon->dayOfYear;
$weekOfYear = $carbon->weekOfYear;
$daysInMonth = $carbon->daysInMonth;
$timestamp = $carbon->timestamp;
$timezone = $carbon->timezone;
$tzName = $carbon->tzName;
$offset = $carbon->offset;
$offsetHours = $carbon->offsetHours;
$dst = $carbon->dst;
$utc = $carbon->utc;
$weekOfMonth = $carbon->weekOfMonth;
$age = $carbon->age;
$quarter = $carbon->quarter;
$yearIso = $carbon->yearIso;
$micro = $carbon->micro;
```

<a id="__isset"></a>

### __isset()

Checks if a property is set.

```php
if (isset($carbon->year)) {
    // ...
}
```

<a id="__set"></a>

### __set()

Sets various date/time properties.

```php
$carbon->year = 2023;
$carbon->month = 1;
$carbon->day = 1;
$carbon->hour = 12;
$carbon->minute = 0;
$carbon->second = 0;
$carbon->timestamp = 1609459200;
$carbon->timezone = 'UTC';
$carbon->tz = 'America/New_York';
```

<a id="year"></a>

### year()

Sets the year.

```php
$carbon->year(2023);
```

<a id="month"></a>

### month()

Sets the month.

```php
$carbon->month(1);
```

<a id="day"></a>

### day()

Sets the day.

```php
$carbon->day(1);
```

<a id="hour"></a>

### hour()

Sets the hour.

```php
$carbon->hour(12);
```

<a id="minute"></a>

### minute()

Sets the minute.

```php
$carbon->minute(0);
```

<a id="second"></a>

### second()

Sets the second.

```php
$carbon->second(0);
```

<a id="setdatetime"></a>

### setDateTime()

Sets date and time.

```php
$carbon->setDateTime(2023, 1, 1, 12, 0, 0);
```

<a id="timestamp"></a>

### timestamp()

Sets the timestamp.

```php
$carbon->timestamp(1609459200);
```

<a id="timezone"></a>

### timezone()

Sets the timezone.

```php
$carbon->timezone('UTC');
```

<a id="tz"></a>

### tz()

Sets the timezone (alias for timezone()).

```php
$carbon->tz('America/New_York');
```

<a id="settimezone"></a>

### setTimezone()

Sets the timezone.

```php
$carbon->setTimezone('UTC');
```

<a id="setnow"></a>

### setNow()

Sets a test now value for testing.

```php
Carbon::setNow(Carbon::create(2023, 1, 1));
```

<a id="gettestnow"></a>

### getTestNow()

Gets the test now value.

```php
$testNow = Carbon::getTestNow();
```

<a id="hastestnow"></a>

### hasTestNow()

Checks if a test now value is set.

```php
if (Carbon::hasTestNow()) {
    // ...
}
```

<a id="hasrelativekeywords"></a>

### hasRelativeKeywords()

Checks if the time string has relative keywords.

```php
$hasRelative = Carbon::hasRelativeKeywords('tomorrow');
```

<a id="resettostringformat"></a>

### resetToStringFormat()

Resets the toString format to default.

```php
Carbon::resetToStringFormat();
```

<a id="settostringformat"></a>

### setToStringFormat()

Sets the format for __toString().

```php
Carbon::setToStringFormat('Y-m-d');
```

<a id="__tostring"></a>

### __toString()

Returns the date/time as a string.

```php
echo $carbon; // 2023-01-01 12:00:00
```

<a id="todatestring"></a>

### toDateString()

Returns the date as a string.

```php
$date = $carbon->toDateString(); // 2023-01-01
```

<a id="toformatteddatestring"></a>

### toFormattedDateString()

Returns a formatted date string.

```php
$date = $carbon->toFormattedDateString(); // Jan 1, 2023
```

<a id="totimestring"></a>

### toTimeString()

Returns the time as a string.

```php
$time = $carbon->toTimeString(); // 12:00:00
```

<a id="todatetimestring"></a>

### toDateTimeString()

Returns date and time as a string.

```php
$datetime = $carbon->toDateTimeString(); // 2023-01-01 12:00:00
```

<a id="todaydatetimestring"></a>

### toDayDateTimeString()

Returns day, date, and time as a string.

```php
$daydatetime = $carbon->toDayDateTimeString(); // Sun, Jan 1, 2023 12:00 PM
```

<a id="toatomstring"></a>

### toAtomString()

Returns the date in Atom format.

```php
$atom = $carbon->toAtomString();
```

<a id="tocookiestring"></a>

### toCookieString()

Returns the date in cookie format.

```php
$cookie = $carbon->toCookieString();
```

<a id="toiso8601string"></a>

### toIso8601String()

Returns the date in ISO 8601 format.

```php
$iso = $carbon->toIso8601String();
```

<a id="torfc822string"></a>

### toRfc822String()

Returns the date in RFC 822 format.

```php
$rfc822 = $carbon->toRfc822String();
```

<a id="torfc850string"></a>

### toRfc850String()

Returns the date in RFC 850 format.

```php
$rfc850 = $carbon->toRfc850String();
```

<a id="torfc1036string"></a>

### toRfc1036String()

Returns the date in RFC 1036 format.

```php
$rfc1036 = $carbon->toRfc1036String();
```

<a id="torfc1123string"></a>

### toRfc1123String()

Returns the date in RFC 1123 format.

```php
$rfc1123 = $carbon->toRfc1123String();
```

<a id="torfc2822string"></a>

### toRfc2822String()

Returns the date in RFC 2822 format.

```php
$rfc2822 = $carbon->toRfc2822String();
```

<a id="torfc3339string"></a>

### toRfc3339String()

Returns the date in RFC 3339 format.

```php
$rfc3339 = $carbon->toRfc3339String();
```

<a id="torssstring"></a>

### toRssString()

Returns the date in RSS format.

```php
$rss = $carbon->toRssString();
```

<a id="tow3cstring"></a>

### toW3cString()

Returns the date in W3C format.

```php
$w3c = $carbon->toW3cString();
```

<a id="eq"></a>

### eq()

Checks if two Carbon instances are equal.

```php
$carbon1 = Carbon::create(2023, 1, 1);
$carbon2 = Carbon::create(2023, 1, 1);
$isEqual = $carbon1->eq($carbon2); // true
```

<a id="ne"></a>

### ne()

Checks if two Carbon instances are not equal.

```php
$isNotEqual = $carbon1->ne($carbon2); // false
```

<a id="gt"></a>

### gt()

Checks if the instance is greater than another.

```php
$carbon1 = Carbon::create(2023, 1, 2);
$carbon2 = Carbon::create(2023, 1, 1);
$isGreater = $carbon1->gt($carbon2); // true
```

<a id="gte"></a>

### gte()

Checks if the instance is greater than or equal to another.

```php
$isGreaterOrEqual = $carbon1->gte($carbon2); // true
```

<a id="lt"></a>

### lt()

Checks if the instance is less than another.

```php
$isLess = $carbon1->lt($carbon2); // false
```

<a id="lte"></a>

### lte()

Checks if the instance is less than or equal to another.

```php
$isLessOrEqual = $carbon1->lte($carbon2); // false
```

<a id="between"></a>

### between()

Checks if the instance is between two dates.

```php
$start = Carbon::create(2023, 1, 1);
$end = Carbon::create(2023, 1, 31);
$isBetween = $carbon->between($start, $end); // true if within range
```

<a id="min"></a>

### min()

Returns the minimum of two dates.

```php
$minDate = $carbon1->min($carbon2);
```

<a id="max"></a>

### max()

Returns the maximum of two dates.

```php
$maxDate = $carbon1->max($carbon2);
```

<a id="isweekday"></a>

### isWeekday()

Checks if the date is a weekday.

```php
$isWeekday = $carbon->isWeekday(); // true if Monday to Friday
```

<a id="isweekend"></a>

### isWeekend()

Checks if the date is a weekend.

```php
$isWeekend = $carbon->isWeekend(); // true if Saturday or Sunday
```

<a id="isyesterday"></a>

### isYesterday()

Checks if the date is yesterday.

```php
$isYesterday = $carbon->isYesterday();
```

<a id="istoday"></a>

### isToday()

Checks if the date is today.

```php
$isToday = $carbon->isToday();
```

<a id="istomorrow"></a>

### isTomorrow()

Checks if the date is tomorrow.

```php
$isTomorrow = $carbon->isTomorrow();
```

<a id="isfuture"></a>

### isFuture()

Checks if the date is in the future.

```php
$isFuture = $carbon->isFuture();
```

<a id="ispast"></a>

### isPast()

Checks if the date is in the past.

```php
$isPast = $carbon->isPast();
```

<a id="isleapyear"></a>

### isLeapYear()

Checks if the year is a leap year.

```php
$isLeap = $carbon->isLeapYear();
```

<a id="issameday"></a>

### isSameDay()

Checks if two dates are the same day.

```php
$isSame = $carbon1->isSameDay($carbon2);
```

<a id="addyears"></a>

### addYears()

Adds years to the date.

```php
$carbon->addYears(2);
```

<a id="addyear"></a>

### addYear()

Adds one year.

```php
$carbon->addYear();
```

<a id="subyear"></a>

### subYear()

Subtracts one year.

```php
$carbon->subYear();
```

<a id="subyears"></a>

### subYears()

Subtracts years.

```php
$carbon->subYears(2);
```

<a id="addmonths"></a>

### addMonths()

Adds months.

```php
$carbon->addMonths(2);
```

<a id="addmonth"></a>

### addMonth()

Adds one month.

```php
$carbon->addMonth();
```

<a id="submonth"></a>

### subMonth()

Subtracts one month.

```php
$carbon->subMonth();
```

<a id="submonths"></a>

### subMonths()

Subtracts months.

```php
$carbon->subMonths(2);
```

<a id="addmonthsnooverflow"></a>

### addMonthsNoOverflow()

Adds months without overflowing to the next month.

```php
$carbon->addMonthsNoOverflow(2);
```

<a id="addmonthnooverflow"></a>

### addMonthNoOverflow()

Adds one month without overflow.

```php
$carbon->addMonthNoOverflow();
```

<a id="submonthnooverflow"></a>

### subMonthNoOverflow()

Subtracts one month without overflow.

```php
$carbon->subMonthNoOverflow();
```

<a id="submonthsnooverflow"></a>

### subMonthsNoOverflow()

Subtracts months without overflow.

```php
$carbon->subMonthsNoOverflow(2);
```

<a id="adddays"></a>

### addDays()

Adds days.

```php
$carbon->addDays(5);
```

<a id="addday"></a>

### addDay()

Adds one day.

```php
$carbon->addDay();
```

<a id="subday"></a>

### subDay()

Subtracts one day.

```php
$carbon->subDay();
```

<a id="subdays"></a>

### subDays()

Subtracts days.

```php
$carbon->subDays(5);
```

<a id="addweekdays"></a>

### addWeekdays()

Adds weekdays.

```php
$carbon->addWeekdays(5);
```

<a id="addweekday"></a>

### addWeekday()

Adds one weekday.

```php
$carbon->addWeekday();
```

<a id="subweekday"></a>

### subWeekday()

Subtracts one weekday.

```php
$carbon->subWeekday();
```

<a id="subweekdays"></a>

### subWeekdays()

Subtracts weekdays.

```php
$carbon->subWeekdays(5);
```

<a id="addweeks"></a>

### addWeeks()

Adds weeks.

```php
$carbon->addWeeks(2);
```

<a id="addweek"></a>

### addWeek()

Adds one week.

```php
$carbon->addWeek();
```

<a id="subweek"></a>

### subWeek()

Subtracts one week.

```php
$carbon->subWeek();
```

<a id="subweeks"></a>

### subWeeks()

Subtracts weeks.

```php
$carbon->subWeeks(2);
```

<a id="addhours"></a>

### addHours()

Adds hours.

```php
$carbon->addHours(5);
```

<a id="addhour"></a>

### addHour()

Adds one hour.

```php
$carbon->addHour();
```

<a id="subhour"></a>

### subHour()

Subtracts one hour.

```php
$carbon->subHour();
```

<a id="subhours"></a>

### subHours()

Subtracts hours.

```php
$carbon->subHours(5);
```

<a id="addminutes"></a>

### addMinutes()

Adds minutes.

```php
$carbon->addMinutes(30);
```

<a id="addminute"></a>

### addMinute()

Adds one minute.

```php
$carbon->addMinute();
```

<a id="subminute"></a>

### subMinute()

Subtracts one minute.

```php
$carbon->subMinute();
```

<a id="subminutes"></a>

### subMinutes()

Subtracts minutes.

```php
$carbon->subMinutes(30);
```

<a id="addseconds"></a>

### addSeconds()

Adds seconds.

```php
$carbon->addSeconds(30);
```

<a id="addsecond"></a>

### addSecond()

Adds one second.

```php
$carbon->addSecond();
```

<a id="subsecond"></a>

### subSecond()

Subtracts one second.

```php
$carbon->subSecond();
```

<a id="subseconds"></a>

### subSeconds()

Subtracts seconds.

```php
$carbon->subSeconds(30);
```

<a id="diffinyears"></a>

### diffInYears()

Gets the difference in years.

```php
$years = $carbon1->diffInYears($carbon2);
```

<a id="diffinmonths"></a>

### diffInMonths()

Gets the difference in months.

```php
$months = $carbon1->diffInMonths($carbon2);
```

<a id="diffinweeks"></a>

### diffInWeeks()

Gets the difference in weeks.

```php
$weeks = $carbon1->diffInWeeks($carbon2);
```

<a id="diffindays"></a>

### diffInDays()

Gets the difference in days.

```php
$days = $carbon1->diffInDays($carbon2);
```

<a id="diffindaysfiltered"></a>

### diffInDaysFiltered()

Gets the difference in days filtered by a callback.

```php
$weekdays = $carbon1->diffInDaysFiltered(function ($date) {
    return $date->isWeekday();
}, $carbon2);
```

<a id="diffinweekdays"></a>

### diffInWeekdays()

Gets the difference in weekdays.

```php
$weekdays = $carbon1->diffInWeekdays($carbon2);
```

<a id="diffinweekenddays"></a>

### diffInWeekendDays()

Gets the difference in weekend days.

```php
$weekends = $carbon1->diffInWeekendDays($carbon2);
```

<a id="diffinhours"></a>

### diffInHours()

Gets the difference in hours.

```php
$hours = $carbon1->diffInHours($carbon2);
```

<a id="diffinminutes"></a>

### diffInMinutes()

Gets the difference in minutes.

```php
$minutes = $carbon1->diffInMinutes($carbon2);
```

<a id="diffinseconds"></a>

### diffInSeconds()

Gets the difference in seconds.

```php
$seconds = $carbon1->diffInSeconds($carbon2);
```

<a id="secondssincemidnight"></a>

### secondsSinceMidnight()

Gets seconds since midnight.

```php
$seconds = $carbon->secondsSinceMidnight();
```

<a id="secondsuntilendofday"></a>

### secondsUntilEndOfDay()

Gets seconds until end of day.

```php
$seconds = $carbon->secondsUntilEndOfDay();
```

<a id="diffforhumans"></a>

### diffForHumans()

Gets a human-readable difference.

```php
$diff = $carbon1->diffForHumans($carbon2);
```

<a id="startofday"></a>

### startOfDay()

Sets to the start of the day.

```php
$carbon->startOfDay();
```

<a id="endofday"></a>

### endOfDay()

Sets to the end of the day.

```php
$carbon->endOfDay();
```

<a id="startofmonth"></a>

### startOfMonth()

Sets to the start of the month.

```php
$carbon->startOfMonth();
```

<a id="endofmonth"></a>

### endOfMonth()

Sets to the end of the month.

```php
$carbon->endOfMonth();
```

<a id="startofyear"></a>

### startOfYear()

Sets to the start of the year.

```php
$carbon->startOfYear();
```

<a id="endofyear"></a>

### endOfYear()

Sets to the end of the year.

```php
$carbon->endOfYear();
```

<a id="startofdecade"></a>

### startOfDecade()

Sets to the start of the decade.

```php
$carbon->startOfDecade();
```

<a id="endofdecade"></a>

### endOfDecade()

Sets to the end of the decade.

```php
$carbon->endOfDecade();
```

<a id="startofcentury"></a>

### startOfCentury()

Sets to the start of the century.

```php
$carbon->startOfCentury();
```

<a id="endofcentury"></a>

### endOfCentury()

Sets to the end of the century.

```php
$carbon->endOfCentury();
```

<a id="startofweek"></a>

### startOfWeek()

Sets to the start of the week.

```php
$carbon->startOfWeek();
```

<a id="endofweek"></a>

### endOfWeek()

Sets to the end of the week.

```php
$carbon->endOfWeek();
```

<a id="next"></a>

### next()

Moves to the next specified day of the week.

```php
$carbon->next(Carbon::MONDAY);
```

<a id="previous"></a>

### previous()

Moves to the previous specified day of the week.

```php
$carbon->previous(Carbon::FRIDAY);
```

<a id="firstofmonth"></a>

### firstOfMonth()

Moves to the first of the month or first specified day.

```php
$carbon->firstOfMonth(Carbon::MONDAY);
```

<a id="lastofmonth"></a>

### lastOfMonth()

Moves to the last of the month or last specified day.

```php
$carbon->lastOfMonth(Carbon::FRIDAY);
```

<a id="nthofmonth"></a>

### nthOfMonth()

Moves to the nth occurrence of a day in the month.

```php
$carbon->nthOfMonth(2, Carbon::MONDAY);
```

<a id="firstofquarter"></a>

### firstOfQuarter()

Moves to the first of the quarter or first specified day.

```php
$carbon->firstOfQuarter(Carbon::MONDAY);
```

<a id="lastofquarter"></a>

### lastOfQuarter()

Moves to the last of the quarter or last specified day.

```php
$carbon->lastOfQuarter(Carbon::FRIDAY);
```

<a id="nthofquarter"></a>

### nthOfQuarter()

Moves to the nth occurrence of a day in the quarter.

```php
$carbon->nthOfQuarter(2, Carbon::MONDAY);
```

<a id="firstofyear"></a>

### firstOfYear()

Moves to the first of the year or first specified day.

```php
$carbon->firstOfYear(Carbon::MONDAY);
```

<a id="lastofyear"></a>

### lastOfYear()

Moves to the last of the year or last specified day.

```php
$carbon->lastOfYear(Carbon::FRIDAY);
```

<a id="nthofyear"></a>

### nthOfYear()

Moves to the nth occurrence of a day in the year.

```php
$carbon->nthOfYear(2, Carbon::MONDAY);
```

<a id="average"></a>

### average()

Calculates the average date between two dates.

```php
$average = $carbon1->average($carbon2);
```

<a id="isbirthday"></a>

### isBirthday()

Checks if the date is the same day and month as another.

```php
$isBirthday = $carbon1->isBirthday($carbon2);
```
