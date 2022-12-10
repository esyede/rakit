# Data Fiktif (Faker)

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Cara Penggunaan](#cara-penggunaan)
-   [Formatter](#formatter)
-   [Opsi Tambahan](#opsi-tambahan)

<a id="pengetahuan-dasar"></a>

# Pengetahuan Dasar

Faker adalah library yang menghasilkan data palsu untuk anda. Library ini sangat berguna untuk mem-bootstrap database anda, mengisi data untuk testing, atau menyamarkan data yang diambil dari server produksi. Library ini diadopsi dari [fzaninotto/faker](https://github.com/fzaninotto/faker) versi 1.5.0.

<a id="cara-penggunaan"></a>

## Cara Penggunaan

Gunakan `Faker::create()` untuk menginisiasi dan mulai menggunakan faker. Setelah diinisiasi, anda hanya tinggal memanggil propertinya sesuai data yang sedang anda butuhkan. Cukup mudah kan? Mari kita coba.

```php
$faker = Faker::create();
```

Secara default, data yang dihasilkan sudah dalam bahasa Indonesia. Namun anda juga bisa menggunakan bahasa inggris tentunya:

```php
$faker = Faker::create('en');
```

Baiklah, untuk contoh dibawah kita akan gunakan defaultnya, yaitu bahasa indonesia

```php
$faker->name; // 'Raihan Nashiruddin';

$faker->address;
// 'Jln. Radio No. 29, Dumai 85136, Kalbar'

$faker->text;
// Sint velit eveniet. Rerum atque repellat voluptatem quia rerum. Numquam excepturi
// beatae sint laudantium consequatur. Magni occaecati itaque sint et sit tempore. Nesciunt
// amet quidem. Iusto deleniti cum autem ad quia aperiam.
// A consectetur quos aliquam. In iste aliquid et aut similique suscipit. Consequatur qui
// quaerat iste minus hic expedita. Consequuntur error magni et laboriosam. Aut aspernatur
// voluptatem sit aliquam. Dolores voluptatum est.
// Aut molestias et maxime. Fugit autem facilis quos vero. Eius quibusdam possimus est.
// Ea quaerat et quisquam. Deleniti sunt quam. Adipisci consequatur id in occaecati.
// Et sint et. Ut ducimus quod nemo ab voluptatum.
```

<a id="formatter"></a>

## Formatter

Setiap properti generator (seperti `name`, `address`, dan `lorem`) disebut "formatter". Berikut adalah daftar formatter yang tersedia secara default:

### Data umum

```php
$faker->randomDigit; // 7
$faker->randomDigitNotNull; // 5
$faker->randomNumber($nbDigits = null); // 79907610
$faker->randomFloat($maxRounds = null, $min = 0, $max = null); // 2.497
$faker->numberBetween($min = 1000, $max = 9000); // 8567
$faker->randomLetter; // 'b'
$faker->randomElements($array = ['a','b','c'], $count = 1); // ['c']
$faker->randomElement($array = ['a','b','c'])); // 'b'
$faker->shuffle('hello, world'); // 'rlo,h eoldlw'
$faker->shuffle([1, 2, 3]); // [2, 1, 3]
$faker->numerify('Hello ###'); // 'Hello 609'
$faker->lexify('Hello ???'); // 'Hello wgt'
$faker->bothify('Hello ##??'); // 'Hello 42jz'
$faker->asciify('Hello ***'); // 'Hello R6+'
$faker->regexify('[0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}'); // '5@c.ojyc'
```

### Teks dummy

```php
$faker->word; // 'aut'
$faker->words($nb = 3); // ['porro', 'sed', 'magni']
$faker->sentence($nbWords = 6); // 'Sit vitae voluptas sint non voluptates.'

$faker->sentences($nb = 3);
// [
//	'Optio quos qui illo error.',
//	'Laborum vero a officia id corporis.',
//	'Saepe provident esse hic eligendi.'
// ]

$faker->paragraph($nbSentences = 3);
// 'Ut ab voluptas sed a nam. Sint autem inventore aut officia aut aut blanditiis. Ducimus eos odit amet et est ut eum.'

$faker->paragraphs($nb = 3);
// [
//	'Quidem ut sunt et quidem est accusamus aut. Fuga est placeat ut.',
//	'Aut nam et eum architecto fugit repellendus illos.',
//	'Possimus omnis aut incidunt sunt.'
// ]

$faker->text($maxNbChars = 200);
// 'Fuga totam reiciendis qui architecto fugiat nemo. Consequatur recusandae qui cupiditate eos quod.'
```

### Data pribadi

```php
$faker->title($gender = null|'male'|'female');     // 'Drs.'
$faker->titleMale;                                 // 'Dr.'
$faker->titleFemale;                               // 'dr.'
$faker->suffix;                                    // 'MPd.'
$faker->name($gender = null|'male'|'female');      // 'Novi Gunawan'
$faker->firstName($gender = null|'male'|'female'); // 'Eva'
$faker->firstNameMale;                             // 'Prima'
$faker->firstNameFemale;                           // 'Novi'
$faker->lastName;                                  // 'Gunawan'
```

### Alamat

```php
$faker->cityPrefix;       // null (hanya tersedia di bahasa inggris)
$faker->secondaryAddress; // null (hanya tersedia di bahasa inggris)
$faker->state;            // 'Sumatera Utara'
$faker->stateAbbr;        // 'Sulbar'
$faker->citySuffix;       // 'Ville'
$faker->streetSuffix;     // 'Street'
$faker->buildingNumber;   // '484'
$faker->city;             // 'Medan'
$faker->streetName;       // 'Cemara'
$faker->streetAddress;    // 'Kpg. Peta No. 14'
$faker->postcode;         // '37445'
$faker->address;          // 'Jln. Cemara No. 363, Madiun 26716, Sumut'
$faker->country;          // 'Kepulauan Virgin Inggris'
$faker->latitude;         // 72.671642
$faker->longitude;        // 82.754482
```

### Nomor telepon

```php
$faker->phoneNumber; // '0248 4157 500'
```

### Perusahaan

```php
$faker->catchPhrase;   // null
$faker->bs;            // null
$faker->company;       // 'PT Zulaika Kuswandari Tbk'
$faker->companyPrefix; // 'CV'
$faker->companySuffix; // '(Persero) Tbk'
```

### Waktu dan tanggal

```php
$faker->unixTime($max = 'now');   // 289052413
$faker->dateTime($max = 'now');   // DateTime('2008-04-25 08:37:17')
$faker->dateTimeAD($max = 'now'); // DateTime('1800-04-29 20:38:49')
$faker->iso8601($max = 'now');    // '1978-12-09T10:10:29+0000'

$faker->date($format = 'Y-m-d', $max = 'now'); // '1979-06-09'
$faker->time($format = 'H:i:s', $max = 'now'); // '20:49:42'

$faker->dateTimeBetween($startDate = '-30 years', $endDate = 'now');
// DateTime('2003-03-15 02:00:49')

$faker->dateTimeThisCentury($max = 'now');
// DateTime('1999-05-30 19:28:21')

$faker->dateTimeThisDecade($max = 'now');
// DateTime('2010-05-29 22:30:48')

$faker->dateTimeThisYear($max = 'now');
// DateTime('2019-10-12 20:52:14')

$faker->dateTimeThisMonth($max = 'now');
// DateTime('2020-02-15 13:46:23')

$faker->amPm($max = 'now');       // 'am'
$faker->dayOfMonth($max = 'now'); // '24'
$faker->dayOfWeek($max = 'now');  // 'Wednesday'
$faker->month($max = 'now');      // '03'
$faker->monthName($max = 'now');  // 'October'
$faker->year($max = 'now');       // '1984'
$faker->century;                  // 'XVII'
$faker->timezone;                 // 'Europe/Bratislava'
```

### Internet

```php
$faker->email;           // 'citra82@yahoo.com'
$faker->safeEmail;       // 'rajata.galih@example.com'
$faker->freeEmail;       // 'tmanullang@gmail.com'
$faker->companyEmail;    // 'diah69@natsir.org'
$faker->freeEmailDomain; // 'yahoo.com'
$faker->safeEmailDomain; // 'example.org'
$faker->userName;        // 'danang26'
$faker->password;        // 'ZZ9_sv5#Ayyf9[3G9'
$faker->domainName;      // 'pradana.web.id'
$faker->domainWord;      // 'adriansyah'
$faker->tld;             // 'go.id'
$faker->url;             // 'https://narpati.net/suscipit-tenetur.html'
$faker->slug;            // 'aut-repellat-commodi-vel-itaque-nihil-id'
$faker->ipv4;            // '29.221.103.82'
$faker->localIpv4;       // '10.242.58.216'
$faker->ipv6;            // 'b86b:5de8:f599:6663:70d6:7942:f55b:ba65'
$faker->macAddress;      // 'BF:6C:E3:3E:70:77'
```

### User agent

```php
$faker->userAgent;
// 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_8_6 rv:6.0) Gecko/20100109 Firefox/37.0'

$faker->chrome;
// 'Mozilla/5.0 (Windows NT 5.01) AppleWebKit/5341 (KHTML, like Gecko) Chrome/38.0.817.0 Mobile Safari/5341'

$faker->firefox;
// 'Mozilla/5.0 (X11; Linux i686; rv:6.0) Gecko/20131219 Firefox/35.0'

$faker->safari;
// 'Mozilla/5.0 (iPad; CPU OS 7_1_2 like Mac OS X; en-US) AppleWebKit/535.16.4 (KHTML, like Gecko) Version/3.0.5 Mobile/8B119 Safari/6535.16.4'

$faker->opera;
// 'Opera/9.23 (X11; Linux x86_64; sl-SI) Presto/2.10.161 Version/10.00'

$faker->internetExplorer;
// 'Mozilla/5.0 (compatible; MSIE 11.0; Windows NT 6.0; Trident/5.0)'
```

### Kartu kredit

```php
$faker->creditCardType; // 'Visa'

$faker->creditCardNumber; // '4556734698567116'

$faker->creditCardExpirationDate;
// [
//   'date' => '2022-01-30 23:30:18.000000',
//   'timezone_type' => 3,
//   'timezone' => 'UTC'
// ]

$faker->creditCardExpirationDateString; // '01/22'

$faker->creditCardDetails;
// [
//   'type' => 'Visa',
//   'number' => '4556030528308',
//   'name' => 'Muriel Mosciski',
//   'expirationDate' => '01/22'
// ]

$faker->swiftBicNumber; // GNLMVB5BWWA
```

### Warna

```php
$faker->hexcolor;        // '#fa3cc2'
$faker->rgbcolor;        // '0,255,122'
$faker->rgbColorAsArray; // [0, 255, 122]
$faker->rgbCssColor;     // 'rgb(0, 255, 122)'
$faker->safeColorName;   // 'fuchsia'
$faker->colorName;       // 'Gainsbor'
```

### File

```php
$faker->fileExtension; // 'avi'
$faker->mimeType;      // 'video/x-msvideo'

// Salin file acak dari sumber ke direktori target
// dan return fullpath atau nama filenya

$faker->file($sourceDir = '/tmp', $targetDir = '/tmp');
// '/path/to/targetDir/13b73edae8443990be1aa8f1a483bc27.jpg'

$faker->file($sourceDir, $targetDir, false);
// '13b73edae8443990be1aa8f1a483bc27.jpg'
```

### Gambar

```php
$faker->imageUrl($width = 640, $height = 480);
// 'http://lorempixel.com/640/480/'

$faker->imageUrl($width, $height, 'cats');
// 'http://lorempixel.com/800/600/cats/'

$faker->imageUrl($width, $height, 'cats', true, 'kittykat');
// 'http://lorempixel.com/800/400/cats/kittykat'

$faker->image($dir = '/tmp', $width = 640, $height = 480);
// '/tmp/13b73edae8443990be1aa8f1a483bc27.jpg'

$faker->image($dir, $width, $height, 'cats');
// 'tmp/13b73edae8443990be1aa8f1a483bc27.jpg' gambar kucing!

$faker->image($dir, $width, $height, 'cats', true, 'Si Kumis');
// 'tmp/13b73edae8443990be1aa8f1a483bc27.jpg' gambar kucing dengan tulisan 'Si Kumis'
```

### UUID (version 4)

```php
$faker->uuid; // '960e75d1-596e-445e-a28a-af45280343ad'
```

### Barcode

```php
$faker->ean13;  // '4006381333931'
$faker->ean8;   // '73513537'
$faker->isbn13; // '9790404436093'
$faker->isbn10; // '4881416324'
```

### Lain-lain

```php
$faker->boolean($chanceOfGettingTrue = 50); // true
$faker->md5;  // 'de99a620c50f2990e87144735cd357e7'
$faker->sha1; // 'f08e7f04ca1a413807ebc47551a40a20a0b4de5c'

$faker->sha256;
// '0061e4c60dac5c1d82db0135a42e00c89ae3a333e7c26485321f24348c7e98a5'

$faker->locale;       // en
$faker->countryCode;  // UK
$faker->languageCode; // en
$faker->currencyCode; // EUR
```

### Bias

```php
// buat nomor acak diantara 10 sampai 20,
// dengan peluang yang lebih banyak ke 20

$faker->biasedNumberBetween($min = 10, $max = 20, $function = 'sqrt');
```

<a id="opsi-tambahan"></a>

## Opsi Tambahan

Faker menyediakan dua opsi tambahan, `unique()` dan `optional()`, untuk dipanggil sebelum provider mana pun dieksekusi. Opsi `optional()` dapat berguna untuk mengisi data pada field yang tidak wajib diisi, seperti nomor telepon seluler; Sedangkan `unique()` diperlukan untuk mengisi field yang tidak dapat menerima dua kali nilai yang sama, seperti primary key.

```php
	$values = [];

	for ($i = 0; $i < 10; $i++) {
		// buat angka acak yang unik
		$values[] = $faker->unique()->randomDigit;
	}

	dd($values); // [4, 1, 8, 5, 0, 2, 6, 9, 7, 3]

	// provider dengan eange yang terbatas akan melempar exception ketika
	// tidak ada lagi value unik yang bisa dibuat.
	$values = [];

	try {
		for ($i = 0; $i < 10; $i++) {
			$values[] = $faker->unique()->randomDigitNotNull;
		}
	} catch (\OverflowException $e) {
		echo 'There are only 9 unique digits not null, Cant generate 10!';
	}

	// anda boleh me-reset unique modifier untuk semua provider dengan cara ini
	$faker->unique($reset = true)->randomDigitNotNull;
	// sehingga tidak akan melempar OverflowException karena unique() sudah di-reset

	// tips: unique() menyimpan satu array nilai per provider

	// optional() terkadang mengabaikan provider untuk
	// me-return nilai default sebagai gantinya (yaitu NULL)
	$values = [];

	for ($i = 0; $i < 10; $i++) {
		// buat angka acak, tetapi kadang boleh NULL
		$values[] = $faker->optional()->randomDigit;
	}

	dd($values); // [1, 4, null, 9, 5, null, null, 4, 6, null]

	// optional() menerima parameter $weight untuk menentukan
	// probabilitas dari default value.

	// 0 artinya selalu me-return default value;
	// 1 artinya selalu me-return provider.
	// Defaultnya adalah 0.5.

	$faker->optional($weight = 0.1)->randomDigit;
	// 90% peluang mendapat NULL

	$faker->optional($weight = 0.9)->randomDigit;
	// 10% peluang mendapat NULL

	// optional() juga menerima parameter $default untuk
	// menentukan default value apa yang ingin anda return.
	// Defaultnya NULL.

	$faker->optional($weight = 0.5, $default = false)->randomDigit;
	// 50% peluang mendapat FALSE

	$faker->optional($weight = 0.9, $default = 'abc')->word;
	// 10% peluang mendapat 'abc'
```
