# Bekerja dengan Tanggal

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
    - [Instansiasi](#instansiasi)
    - [Getter](#getter)
    - [Setter](#setter)
    - [Magic Setter](#magic-setter)
    - [Format Ke String](#format-ke-string)
- [Format Umum](#format-umum)
    - [Komparasi](#komparasi)
    - [Penambahan dan Pengurangan](#penambahan-dan-pengurangan)
    - [Selisih](#selisih)
    - [Waktu yang Lalu](#waktu-yang-lalu)
    - [Konstanta](#konstanta)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Komponen `Date` ini meng-extends kelas [DateTime](http://www.php.net/manual/en/class.datetime.php) bawaan PHP sehingga memiliki semua fungsi yang diwarisi dari kelas `DateTime` induknya. Hal ini memungkinkan anda untuk mengakses fungsionalitas dasarnya jika anda melihat sesuatu yang hilang di `Date` tetapi ada di `DateTime`.


<a id="instansiasi"></a>
### Instansiasi

Tersedia 2 cara yang tersedia untuk membuat instance baru dari kelas `Date` ini, yaitu via constructor dan via static method:

#### Instansiasi via constructor:

```php
$date = new Date(); // sama dengan Date::now();

$date = new Date('first day of January 2020', 'Asia/Jakarta');

echo get_class($date); // 'System\Date'
```

Pada contoh diatas, timezone (di paremeter ke-2) dioper sebagai string, bukan sebagai instance `\DateTimeZone`. Semua parameter DateTimeZone telah diurus di balik layar sehingga anda dapat secara mengoper instance DateTimeZone atau string.

#### Instansiasi via static method:
```php
$now = Date::now();

$london = Date::now(new \DateTimeZone('Europe/London'));

// atau langsung saja oper string
$london = Date::now('Europe/London');
```

Sebagian besar static method `createXXX` memungkinkan anda mengoper argumen sebanyak yang anda inginkan dan akan memberikan default value untuk parameter yang lainnya jika tidak diisi. Pada umumnya default valuenya adalah tanggal, waktu, atau timezone saat ini.

```php
Date::createFromDate($year, $month, $day, $timezone);
Date::createFromTime($hour, $minute, $second, $timezone);
Date::create($year, $month, $day, $hour, $minute, $second, $timezone);
```

Method `createFromDate()` akan menetapkan waktu default ke now (waktu sekarang). Sedangkan `createFromTime()` akan menetapkan tanggal default menjadi hari ini. Lalu `create()` akan menetapkan default parameter `NULL` ke masing-masing value.

Seperti sebelumnya, `$timezone` default ke timezone saat ini dan sebaliknya bisa diisi instance DateTimeZone atau cukup string timezone saja.

Satu-satunya pengecualian untuk default value (meniru kelas bawaan PHP yang mendasarinya) terjadi ketika value jam ditentukan tetapi tidak ada menit atau detik, value tersebut akan secara default di-set ke 0.

```php
$tahunBaru = Date::createFromDate(null, 12, 31); // Tahun default ke tahun ini

$Y2K = Date::create(2000, 1, 1, 0, 0, 0);
$jugaY2K = Date::create(1999, 12, 31, 24);

$siangWita = Date::createFromTime(12, 0, 0, 'Asia/Ujung_Pandang');

// Menit dua digit tidak ditemukan
try {
    Date::create(2020, 5, 21, 22, -2, 0);
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage();
}

Date::createFromFormat($format, $time, $timezone);
```

Method `createFromFormat()` adalah wrapper dari method PHP `DateTime::createFromFormat()`. Perbedaannya, parameter `$timezone` dapat berupa instance DateTimeZone atau hanya string.

Selain itu, jika ada kesalahan pada format tanggal yang diberikan, method ini akan memanggil `DateTime::getLastErrors()` dan kemudian menampilkan pesan errirnya via `InvalidArgumentException`.

```php
echo Date::createFromFormat('Y-m-d H', '1975-05-21 22')
    ->toDateTimeString(); // 1975-05-21 22:00:00
```

Dua fungsi create terakhir digunakan untuk bekerja dengan [Unix Timestamp](http://en.wikipedia.org/wiki/Unix_time). Yang pertama akan membuat instance `Date` sesuai timestamp yang diberikan dan akan menyetel timezone juga, atau menetapkan defaultnya ke timezone saat ini.

Yang kedua, method `createFromTimestampUTC()` agak berbeda karena timezonenya akan tetap dalam UTC (GMT). Cara kerjanya sama seperti `Date::createFromFormat('@'.$timestamp)` tetapi kami membuatnya sedikit lebih eksplisit, yaitu timestamp negatif juga diperbolehkan.

```php
echo Date::createFromTimeStamp(-1)
    ->toDateTimeString(); // 1969-12-31 18:59:59

echo Date::createFromTimeStamp(-1, 'Europe/London')
    ->toDateTimeString(); // 1970-01-01 00:59:59

echo Date::createFromTimeStampUTC(-1)
    ->toDateTimeString(); // 1969-12-31 23:59:59
```

Anda juga dapat membuat salinan dari instance Date yang sudahada. Dengan method `copy()`, value tanggal, waktu, dan timezone semuanya disalin ke instance yang baru.

```php
$date = Date::now();
echo $date->diffInYears($date->copy()->addYear());  // 1

// $date tidak berubah dan tetap bervalue Date:now()
```

Terakhir, jika anda meng-extends instance `\DateTime` dari library lain, anda tetap bisa membuat instance Date melalui method `instance()`:

```php
$date = new \DateTime('first day of May 2020'); // instance dari API lain

$date = Date::instance($date);

echo get_class($date); // 'System\Date'
echo $date->toDateTimeString(); // '2020-05-01 00:00:00'
```

<a id="getter"></a>
### Getter

Getter diimplementasikan melalui bantuan magic method `__get()` bawaan PHP. Ini memungkinkan anda untuk mengakses return value dari method seolah-olah itu adalah properti.

```php
$date = Date::create(2012, 9, 5, 23, 26, 11);

// Getter ini secara khusus me-return integer
dd($date->year);                              // int(2012)
dd($date->month);                             // int(9)
dd($date->day);                               // int(5)
dd($date->hour);                              // int(23)
dd($date->minute);                            // int(26)
dd($date->second);                            // int(11)
dd($date->dayOfWeek);                         // int(3)
dd($date->dayOfYear);                         // int(248)
dd($date->weekOfYear);                        // int(36)
dd($date->daysInMonth);                       // int(30)
dd($date->timestamp);                         // int(1346901971)

// Dihitung v.s. now() pada timezone yang sama
dd(Date::createFromDate(1975, 5, 21)->age);    // int(37)

dd($date->quarter);                            // int(3)

// Mereturn integer jumlah selisih detik dari UTC (termasuk tanda +/-)
dd(Date::createFromTimestampUTC(0)->offset); // int(0)
dd(Date::createFromTimestamp(0)->offset);    // int(-18000)

// Mereturn integer jumlah selisih jam dari UTC (termasuk tanda +/-)
dd(Date::createFromTimestamp(0)->offsetHours); // int(-5)

// Cek apakah daylight saving diaktifkan
dd(Date::createFromDate(2012, 1, 1)->dst);     // bool(false)

// Ambil instance DateTimeZone
echo get_class(Date::now()->timezone); // DateTimeZone
echo get_class(Date::now()->tz);       // DateTimeZone

// Ambil nama instance DateTimeZone, shortcut untuk ->timezone->getName()
echo Date::now()->timezoneName; // America/Toronto
echo Date::now()->tzName;       // America/Toronto
```

<a id="setter"></a>
### Setter

Setter berikut diimplementasikan melalui magic method `__set()` bawaan PHP. Patut diperhatikan bahwa tidak satupun setter, dengan pengecualian saat men-set timezone secara eksplisit, yang akan mengubah timezone instance. Secara khusus, men-set timestamp tidak akan mengubah timezone ke UTC.

```php
$date = Date::now();

$date->year = 1975;
$date->month = 13; // akan memaksa year++ dan month = 1
$date->month = 5;
$date->day = 21;
$date->hour = 22;
$date->minute = 32;
$date->second = 5;

$date->timestamp = 169957925; // ini tidak akan mengubah timezone

// Set timezone via instance \DateTimeZone atau string
$date->timezone = new \DateTimeZone('Europe/London');
$date->timezone = 'Europe/London';
$date->tz = 'Europe/London';
```

<a id="magic-setter"></a>
### Magic Setter

Sama dengan setter biasa, magic setter hanyalah cara lain untuk men-set data. Opsi ini disediakan untuk anda yang lebih menyukai chainability.

```php
$date = Date::now();

$date->year(1975)
    ->month(5)
    ->day(21)
    ->hour(22)
    ->minute(32)
    ->second(5)
    ->toDateTimeString();


$date->setDate(1975, 5, 21)->setTime(22, 32, 5)->toDateTimeString();
$date->setDateTime(1975, 5, 21, 22, 32, 5)->toDateTimeString();
$date->timestamp(169957925)->timezone('Europe/London');
$date->tz('America/Toronto')->setTimezone('America/Vancouver');
```

<a id="format-ke-string"></a>
### Format Ke String

Semua method `toXXXString()` yang tersedia bergantung pada method [DateTime::format()](http://php.net/manual/en/datetime.format.php) bawaan PHP. Dengan bantuan magic method `__toString()`, instance Date akan dicetak sebagai string ketika digunakan dalam konteks string.

```php
$date = Date::create(1975, 12, 25, 14, 15, 16);

dd($date->toDateTimeString() == $date); // bool(true) => menggunakan __toString()
echo $date->toDateString();             // 1975-12-25
echo $date->toFormattedDateString();    // Dec 25, 1975
echo $date->toTimeString();             // 14:15:16
echo $date->toDateTimeString();         // 1975-12-25 14:15:16
echo $date->toDayDateTimeString();      // Thu, Dec 25, 1975 2:15 PM

// ... tentu saja format() juga masih bisa dipakai

echo $date->format('l jS \\of F Y h:i:s A');
// output: Thursday 25th of December 1975 02:15:16 PM
```

<a id="format-umum"></a>
## Format Umum

Berikut ini adalah wrapper untuk format umum yang disediakan di kelas [DateTime](http://www.php.net/manual/en/class.datetime.php).

```php
$date = Date::now();

echo $date->toAtomString(); // sama dengan $date->format(DateTime::ATOM);
echo $date->toCookieString();
echo $date->toIso8601String();
echo $date->toRfc822String();
echo $date->toRfc850String();
echo $date->toRfc1036String();
echo $date->toRfc1123String();
echo $date->toRfc2822String();
echo $date->toRfc3339String();
echo $date->toRssString();
echo $date->toW3cString();
```

<a id="komparasi"></a>
### Komparasi

Komponen ini juga menyediakan fungsi komparasi melalui method-method berikut. Ingatlah bahwa komparasi dilakukan dalam timezone UTC sehingga segala sesuatunya tidak selalu seperti yang terlihat.

```php
$first = Date::create(2012, 9, 5, 23, 26, 11);
$second = Date::create(2012, 9, 5, 20, 26, 11, 'America/Vancouver');

echo $first->toDateTimeString();  // 2012-09-05 23:26:11
echo $second->toDateTimeString(); // 2012-09-05 20:26:11

dd($first->eq($second));  // bool(true)
dd($first->ne($second));  // bool(false)
dd($first->gt($second));  // bool(false)
dd($first->gte($second)); // bool(true)
dd($first->lt($second));  // bool(false)
dd($first->lte($second)); // bool(true)

$first->setDateTime(2012, 1, 1, 0, 0, 0);
$second->setDateTime(2012, 1, 1, 0, 0, 0); // ingat, timezonenya 'America/Vancouver'

dd($first->eq($second));  // bool(false)
dd($first->ne($second));  // bool(true)
dd($first->gt($second));  // bool(false)
dd($first->gte($second)); // bool(false)
dd($first->lt($second));  // bool(true)
dd($first->lte($second)); // bool(true)
```

Untuk menangani kasus yang umum saat mengkomparasikan tanggal, ada beberapa fungsi pembantu yang bisa anda coba:

```php
$date = Date::now();

$date->isWeekday();
$date->isWeekend();
$date->isYesterday();
$date->isToday();
$date->isTomorrow();
$date->isFuture();
$date->isPast();
$date->isLeapYear();
```

<a id="penambahan-dan-pengurangan"></a>
### Penambahan dan Pengurangan

Selain itu, juga telah disertakan bantuan untuk operasi penambahan dan pengurangan. Mari kita lihat contohnya:

```php
    $date = Date::create(2012, 1, 31, 0);

    echo $date->toDateTimeString(); // 2012-01-31 00:00:00

    echo $date->addYears(5);        // 2017-01-31 00:00:00
    echo $date->addYear();          // 2018-01-31 00:00:00
    echo $date->subYear();          // 2017-01-31 00:00:00
    echo $date->subYears(5);        // 2012-01-31 00:00:00

    echo $date->addMonths(60);      // 2017-01-31 00:00:00

    // sama dengan $date->month($date->month + 1);
    echo $date->addMonth();         // 2017-03-03 00:00:00

    echo $date->subMonth();         // 2017-02-03 00:00:00
    echo $date->subMonths(60);      // 2012-02-03 00:00:00

    echo $date->addDays(29);        // 2012-03-03 00:00:00
    echo $date->addDay();           // 2012-03-04 00:00:00
    echo $date->subDay();           // 2012-03-03 00:00:00
    echo $date->subDays(29);        // 2012-02-03 00:00:00

    echo $date->addWeekdays(4);     // 2012-02-09 00:00:00
    echo $date->addWeekday();       // 2012-02-10 00:00:00
    echo $date->subWeekday();       // 2012-02-09 00:00:00
    echo $date->subWeekdays(4);     // 2012-02-03 00:00:00

    echo $date->addWeeks(3);        // 2012-02-24 00:00:00
    echo $date->addWeek();          // 2012-03-02 00:00:00
    echo $date->subWeek();          // 2012-02-24 00:00:00
    echo $date->subWeeks(3);        // 2012-02-03 00:00:00

    echo $date->addHours(24);       // 2012-02-04 00:00:00
    echo $date->addHour();          // 2012-02-04 01:00:00
    echo $date->subHour();          // 2012-02-04 00:00:00
    echo $date->subHours(24);       // 2012-02-03 00:00:00

    echo $date->addMinutes(61);     // 2012-02-03 01:01:00
    echo $date->addMinute();        // 2012-02-03 01:02:00
    echo $date->subMinute();        // 2012-02-03 01:01:00
    echo $date->subMinutes(61);     // 2012-02-03 00:00:00

    echo $date->addSeconds(61);     // 2012-02-03 00:01:01
    echo $date->addSecond();        // 2012-02-03 00:01:02
    echo $date->subSecond();        // 2012-02-03 00:01:01
    echo $date->subSeconds(61);     // 2012-02-03 00:00:00

    $date = Date::create(2012, 1, 31, 12, 0, 0);
    echo $date->startOfDay(); // 2012-01-31 00:00:00

    $date = Date::create(2012, 1, 31, 12, 0, 0);
    echo $date->endOfDay(); // 2012-01-31 23:59:59

    $date = Date::create(2012, 1, 31, 12, 0, 0);
    echo $date->startOfMonth(); // 2012-01-01 00:00:00

    $date = Date::create(2012, 1, 31, 12, 0, 0);
    echo $date->endOfMonth(); // 2012-01-31 23:59:59
```

Agar lebih menyenangkan, anda juga boleh mengoper angka negatif ke method `addXXX()` dan `subXXX()`.


<a id="selisih"></a>
### Selisih

Anda juga dapat menghitung selisih waktu dengan library ini. Mari kita lihat contoh - contohnya:

```php
// Date::diffInYears(Date $date = null, $abs = true)

echo Date::now('America/Vancouver')
    ->diffInSeconds(Date::now('Europe/London')); // 0

$ottawa = Date::createFromDate(2000, 1, 1, 'America/Toronto');
$vancouver = Date::createFromDate(2000, 1, 1, 'America/Vancouver');
echo $ottawa->diffInHours($vancouver); // 3

echo $ottawa->diffInHours($vancouver, false); // 3
echo $vancouver->diffInHours($ottawa, false); // -3

$date = Date::create(2012, 1, 31, 0);
echo $date->diffInDays($date->copy()->addMonth()); // 31
echo $date->diffInDays($date->copy()->subMonth(), false); // -31

$date = Date::create(2012, 4, 30, 0);
echo $date->diffInDays($date->copy()->addMonth()); // 30
echo $date->diffInDays($date->copy()->addWeek()); // 7

$date = Date::create(2012, 1, 1, 0);
echo $date->diffInMinutes($date->copy()->addSeconds(59)); // 0
echo $date->diffInMinutes($date->copy()->addSeconds(60)); // 1
echo $date->diffInMinutes($date->copy()->addSeconds(119)); // 1
echo $date->diffInMinutes($date->copy()->addSeconds(120)); // 2

// Fungsi lain yang juga bisa dipakai
// diffInYears(), diffInMonths(), diffInDays()
// diffInHours(), diffInMinutes(), diffInSeconds()
```

>  Di fungsi `DateTime::diff()` bawaan PHP, interval 61 detik akan di-return sebagai 1 menit dan 1 detik, sedangkan ungsi `diffInMinutes()` hanya akan me-return 1.

<a id="waktu-yang-lalu"></a>
### Waktu yang Lalu

Pastinya akan lebih mudah untuk membca `1 bulan yang lalu` ketimbang `30 hari yang lalu`. Tenang saja, fungsionalitas itu juga sudah tersedia. Yuk kita simak!

```php
Date::now()->subDays(5)->diffForHumans(); // 5 hari yang lalu

Date::now()->diffForHumans(Date::now()->subYear()); // 1 tahun setelahnya

$date = Date::createFromDate(2011, 2, 1);

$date->diffForHumans($date->copy()->addMonth()); // 28 hari sebelumnya
$date->diffForHumans($date->copy()->subMonth()); // 1 bulan setelahnya

Date::now()->addSeconds(5)->diffForHumans(); // 5 detik dari sekarang

```

>  Di fungsi ini, 1 bulan selalu berisi 30 hari dan 1 tahun selalu berisi 365 hari.


Dan berikut adalah tabel rujukan untuk fungsionalitas ini:

| Komparasi                                | Contoh Output                              |
| ---------------------------------------- | ------------------------------------------ |
| value di masa lampau ke default sekarang | 1 jam yang lalu, 5 bulan yang lalu         |
| value di masa depan ke default sekarang  | 1 jam dari sekarang, 5 bulan dari sekarang |
| value di masa lampau ke another value    | 1 jam sebelumnya, 5 bulan sebelumnya       |
| value di masa depan ke value lain        | 1 jam setelahnya, 5 bulan setelahnya       |

<a id="konstanta"></a>
### Konstanta

Berikut adalah konstanta yang tersedia di kelas `Date`:

| Konstanta          | Default Value |
| ------------------ | ------------- |
| SUNDAY             | 0             |
| MONDAY             | 1             |
| TUESDAY            | 2             |
| WEDNESDAY          | 3             |
| THURSDAY           | 4             |
| FRIDAY             | 5             |
| SATURDAY           | 6             |
| MONTHS_PER_YEAR    | 12            |
| HOURS_PER_DAY      | 24            |
| MINUTES_PER_HOUR   | 60            |
| SECONDS_PER_MINUTE | 60            |

Contoh penggunaan:

```php
$date = Date::createFromDate(2020, 12, 5);

if ($date->dayOfWeek === Date::SATURDAY) {
    echo 'Hore! besok hari minggu!';
}
```
