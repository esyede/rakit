# Date

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Instansiasi](#instansiasi)
- [Tambah & Kurang](#tambah--kurang)
- [Selisih](#selisih)
- [Komparasi](#komparasi)
- [Fitur Tambahan](#fitur-tambahan)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Komponen `Date` disediakan untuk membantu anda ketika bekerja dengan tanggal. Komponen ini
sengaja dibuat sangat sederhana agar anda tidak harus repot mengingat banyak nama method.


<a id="instansiasi"></a>
## Instansiasi

Tersedia 2 cara untuk mmenginstansiasi kelas `Date` ini, yaitu via
constructor dan via static method:

```php
$date = new Date();

$date = Date::make();
```

Anda juga boleh mengoper string tanggal target saat instansiasi:

```php
$date = new Date('2021-05-23 06:00:00');

$date = Date::make('2021-05-23 06:00:00');
```

Selain string tanggal, anda juga boleh mengoper objek `DateTime`, string literal dan timestamp:

```php
$date = Date::make(new \DateTime());

$date = Date::make('last sunday');

$date = Date::make(1621987200);
```

>  Anda dapat memasukkan string format waktu sesuai dokumentasi
   [Supported Date and Time Formats](https://www.php.net/manual/en/datetime.formats.php) milik PHP.


<a id="tambah--kurang"></a>
## Tambah & Kurang

Gunakan method `remake()` untuk melakukan operasi penambahan dan pengurangan seperti berikut:


#### Tambah

```php
$date = '2021-05-23';

return Date::make($date)->remake('+ 3 days'); // 2021-05-26 (bertambah 3 hari)
```


#### Kurang

```php
$date = '2021-05-23';

return Date::make($date)->remake('- 3 days'); // 2021-05-20 00:00:00 (berkurang 3 hari)
```


<a id="selisih"></a>
## Selisih

Untuk mencari selisih antara 2 buah tanggal, gunakan method `diff()` seperti berikut:

```php
$diff = Date::diff('2021-05-23', '2020-01-01');

return $diff->days; // 508 hari

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

Anda juga dapat mengabaikan parameter ke-dua untuk mencari selisih tanggal pertama
dengan tanggal saat ini:

```php
return Date::diff('2021-05-23');
```


<a id="komparasi"></a>
## Komparasi

Jika anda perlu mengkomparasikan 2 buah tanggal, kami juga telah menyediakannya untuk anda:

#### Sama Dengan

```php
$date = '2021-05-23 00:02:00';

return Date::eq($date, '2021-05-23 00:02:00'); // true
```


#### Lebih Dari

```php
$date = '2021-05-23 00:02:10'; // + 10 detik

return Date::gt($date, '2021-05-23 00:02:00'); // true
```

#### Kurang Dari

```php
$date = '2021-05-23 00:01:50'; // - 10 detik

return Date::lt($date, '2021-05-23 00:02:00'); // true
```


#### Lebih Dari Atau Sama Dengan

```php
$date = '2021-05-23 00:02:10'; // + 10 detik

return Date::gte($date, '2021-05-23 00:02:10'); // true, sama dengan
return Date::gte($date, '2021-05-23 00:02:00'); // true, lebih besar
```


#### Kurang Dari Atau Sama Dengan

```php
$date = '2021-05-23 00:01:50'; // - 10 detik

return Date::lte($date, '2021-05-23 00:02:00'); // true, lebih kecil dari
return Date::lte($date, '2021-05-23 00:01:50'); // true, sama dengan
```


<a id="fitur-tambahan"></a>
## Fitur Tambahan

Selain fitur-fitur diatas, kami juga tlah menyediakan fitur tambahan yang
pastinya akan semakin memudahkan pekerjaan anda:


#### Mengambil Timestamp

```php
$date = Date::make('2021-05-23');

return $date->timestamp(); // 1621728000
```


#### Waktu Saat Ini

```php
return Date::now();

// atau,

return Date::make()->format('Y-m-d H:i:s');
```

Jika diperlukan, anda juga dapat mengubah format waktunya
sesuai format yang anda inginkan, contohnya seperti ini:

```php
return Date::now('F j, Y H:i');
```


#### Format

```php
$date = Date::make('2021-05-23');

return $date->format('F j, Y'); // May 23, 2021
```


#### Time Ago

```php
return Date::make('now - 15 minutes'); // 15 menit yang lalu

return Date::make('now + 20 minutes'); // 20 menit dari sekarang
```


#### Clone

```php
$date = Date::make('2012-04-05');

$clone = $date->remake('+3 days', true); // clone dan +3 hari


return $date;  // 2021-05-23 00:00:00
return $clone; // 2021-05-26 00:00:00
```
