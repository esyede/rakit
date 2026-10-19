# Fake Data (Faker)

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Usage](#usage)
-   [Formatters](#formatters)
-   [Additional Options](#additional-options)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Faker generates fake data for seeding a database, filling test fixtures or anonymizing production data. It is adopted from [fzaninotto/faker](https://github.com/fzaninotto/faker) 1.5.0.

<a id="usage"></a>

## Usage

`Faker::create()` builds a generator; then read the property named after the data you want:

```php
$faker = Faker::create();

$faker->name;    // 'John Doe'
$faker->address; // '123 Main St, New York 12345, NY'
$faker->text;    // 'Sint velit eveniet. Rerum atque repellat voluptatem quia rerum...'
```

The locale follows the `language` option in `application/config/application.php` (`'id'` by default). Pass `'en'` (English) or `'id'` (Indonesian) to choose explicitly; any other value throws an `InvalidArgumentException`:

```php
$faker = Faker::create('en');
```

<a id="formatters"></a>

## Formatters

Each generator property or method (such as `name`, `address` or `text`) is called a "formatter". The examples below use the English locale.

### General Data

```php
$faker->randomDigit; // 7
$faker->randomDigitNotNull; // 5
$faker->randomNumber($nbDigits = null); // 79907610
$faker->randomFloat($nbMaxDecimals = null, $min = 0, $max = null); // 2.497
$faker->numberBetween($min = 1000, $max = 9000); // 8567
$faker->randomLetter; // 'b'
$faker->randomElements($array = ['a','b','c'], $count = 1); // ['c']
$faker->randomElement($array = ['a','b','c']); // 'b'
$faker->shuffle('hello, world'); // 'rlo,h eoldlw'
$faker->shuffle([1, 2, 3]); // [2, 1, 3]
$faker->numerify('Hello ###'); // 'Hello 609'
$faker->lexify('Hello ???'); // 'Hello wgt'
$faker->bothify('Hello ##??'); // 'Hello 42jz'
$faker->asciify('Hello ***'); // 'Hello R6+'
$faker->regexify('[0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}'); // '5@c.ojyc'
```

### Dummy Text

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

### Personal Data

```php
$faker->title($gender = null|'male'|'female');     // 'Mr.'
$faker->titleMale;                                 // 'Mr.'
$faker->titleFemale;                               // 'Mrs.'
$faker->suffix;                                    // 'Jr.'
$faker->name($gender = null|'male'|'female');      // 'John Doe'
$faker->firstName($gender = null|'male'|'female'); // 'Jane'
$faker->firstNameMale;                             // 'John'
$faker->firstNameFemale;                           // 'Jane'
$faker->lastName;                                  // 'Doe'
```

### Address

```php
$faker->cityPrefix;       // 'Lake'
$faker->secondaryAddress; // 'Apt. 123'
$faker->state;            // 'California'
$faker->stateAbbr;        // 'CA'
$faker->citySuffix;       // 'ton'
$faker->streetSuffix;     // 'Street'
$faker->buildingNumber;   // '484'
$faker->city;             // 'New York'
$faker->streetName;       // 'Main Street'
$faker->streetAddress;    // '123 Main St'
$faker->postcode;         // '12345'
$faker->address;          // '123 Main St, New York 12345, NY'
$faker->country;          // 'United States'
$faker->latitude;         // 72.671642
$faker->longitude;        // 82.754482
```

### Phone Number

```php
$faker->phoneNumber; // '0248 4157 500'
```

### Company

```php
$faker->catchPhrase;   // 'Proactive dedicated project'
$faker->bs;            // 'leverage synergistic e-commerce'
$faker->company;       // 'Doe Inc.'
$faker->companyPrefix; // 'LLC'
$faker->companySuffix; // 'Inc.'
```

### Date and Time

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
$faker->email;           // 'john.doe@example.com'
$faker->safeEmail;       // 'jane.doe@example.com'
$faker->freeEmail;       // 'john@gmail.com'
$faker->companyEmail;    // 'jane@doe.com'
$faker->freeEmailDomain; // 'gmail.com'
$faker->safeEmailDomain; // 'example.com'
$faker->userName;        // 'johndoe'
$faker->password;        // 'ZZ9_sv5#Ayyf9[3G9'
$faker->domainName;      // 'doe.com'
$faker->domainWord;      // 'doe'
$faker->tld;             // 'com'
$faker->url;             // 'https://doe.com/aut-repellat-commodi'
$faker->slug;            // 'aut-repellat-commodi-vel-itaque-nihil-id'
$faker->ipv4;            // '29.221.103.82'
$faker->localIpv4;       // '10.242.58.216'
$faker->ipv6;            // 'b86b:5de8:f599:6663:70d6:7942:f55b:ba65'
$faker->macAddress;      // 'BF:6C:E3:3E:70:77'
```

### User Agent

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

### Credit Card

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

### Color

```php
$faker->hexcolor;        // '#fa3cc2'
$faker->rgbcolor;        // '0,255,122'
$faker->rgbColorAsArray; // [0, 255, 122]
$faker->rgbCssColor;     // 'rgb(0, 255, 122)'
$faker->safeColorName;   // 'fuchsia'
$faker->colorName;       // 'Gainsboro'
```

### File

```php
$faker->fileExtension; // 'avi'
$faker->mimeType;      // 'video/x-msvideo'

// Copy a random file from source to target directory
// and return the fullpath or filename

$faker->file($sourceDir = '/tmp', $targetDir = '/tmp');
// '/path/to/targetDir/13b73edae8443990be1aa8f1a483bc27.jpg'

$faker->file($sourceDir, $targetDir, false);
// '13b73edae8443990be1aa8f1a483bc27.jpg'
```

### Image

```php
$faker->imageUrl($width = 640, $height = 480);
// 'https://placehold.co/640/480/jpg'

$faker->imageUrl($width, $height, 'cdcdcd');
// 'https://placehold.co/800/400/cdcdcd/jpg'

$faker->imageUrl($width, $height, 'cdcdcd', 'ffffff', 'Hello World');
// 'https://placehold.co/800/400/cdcdcd/ffffff/jpg?text=Hello+World'

$faker->image($dir = '/tmp', $width = 640, $height = 480);
// '/tmp/13b73edae8443990be1aa8f1a483bc27.jpg'

$faker->image($dir, $width, $height, 'cdcdcd');
// '/tmp/13b73edae8443990be1aa8f1a483bc27.jpg' image with #cdcdcd background color

$faker->image($dir, $width, $height, 'cdcdcd', 'ffffff', 'Hello World');
// '/tmp/13b73edae8443990be1aa8f1a483bc27.jpg' image with #cdcdcd background color, #ffffff foreground color, and 'Hello World' text
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

### Miscellaneous

```php
$faker->boolean($chanceOfGettingTrue = 50); // true
$faker->md5;  // 'de99a620c50f2990e87144735cd357e7'
$faker->sha1; // 'f08e7f04ca1a413807ebc47551a40a20a0b4de5c'

$faker->sha256;
// '0061e4c60dac5c1d82db0135a42e00c89ae3a333e7c26485321f24348c7e98a5'

$faker->locale;       // 'en_US'
$faker->countryCode;  // 'GB'
$faker->languageCode; // en
$faker->currencyCode; // EUR
```

### Bias

```php
// create a random number between 10 and 20,
// with more chances to get closer to 20

$faker->biasedNumberBetween($min = 10, $max = 20, $function = 'sqrt');
```

<a id="additional-options"></a>

## Additional Options

Call `unique()` or `optional()` before a formatter to modify its result.

`unique()` never returns the same value twice for a given formatter, which is handy for columns such as primary keys. When the values run out it throws an `OverflowException`; pass `true` to reset the tracked values:

```php
$values = [];

for ($i = 0; $i < 10; $i++) {
    $values[] = $faker->unique()->randomDigit;
}
// [4, 1, 8, 5, 0, 2, 6, 9, 7, 3]

try {
    for ($i = 0; $i < 10; $i++) {
        $faker->unique()->randomDigitNotNull; // only 9 possible values
    }
} catch (\OverflowException $e) {
    // Out of unique values
}

$faker->unique(true)->randomDigitNotNull; // reset, no exception
```

`optional($weight = 0.5, $default = null)` sometimes returns `$default` instead of calling the formatter, which is useful for optional fields such as phone numbers. `$weight` is the chance of getting a real value (0 = always the default, 1 = always the formatter):

```php
$faker->optional()->randomDigit;            // 50% chance of NULL
$faker->optional(0.1)->randomDigit;         // 90% chance of NULL
$faker->optional(0.9, 'abc')->word;         // 10% chance of 'abc'
```
