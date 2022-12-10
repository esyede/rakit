# Helper

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [List Helper](#list-helper)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Rakit menyertakan berbagai fungsi 'pembantu' yang dapat diakses secara global.
Banyak dari fungsi ini juga digunakan didalam sistem rakit;
dan tentu saja, anda juga boleh menggunakannya dalam aplikasi anda jika diperlukan.

<a id="list-helper"></a>

## List Helper

Berikut adalah daftar helper bawaan yang tersedia:

|                             |                                   |                                   |                                 |                                   |                                   |
| --------------------------- | --------------------------------- | --------------------------------- | ------------------------------- | --------------------------------- | --------------------------------- |
| [e](#e)                     | [dd](#dd)                         | [dump](#dump)                     | [bd](#bd)                       | [\_\_](#__)                       | [is_cli](#is_cli)                 |
| [data_fill](#data_fill)     | [data_get](#data_get)             | [data_set](#data_set)             | [retry](#retry)                 | [facile_to_json](#facile_to_json) | [head](#head)                     |
| [last](#last)               | [url](#url)                       | [asset](#asset)                   | [action](#action)               | [route](#route)                   | [redirect](#redirect)             |
| [csrf_field](#csrf_field)   | [root_namespace](#root_namespace) | [class_basename](#class_basename) | [value](#value)                 | [view](#view)                     | [render](#render)                 |
| [render_each](#render_each) | [yield_content](#yield_content)   | [yield_section](#yield_section)   | [section_start](#section_start) | [section_stop](#section_stop)     | [get_cli_option](#get_cli_option) |
| [system_os](#system_os)     |

<a id="e"></a>

### e

Fungsi `e` menjalankan fungsi [htmlspecialchars](https://php.net/htmlspecialchars) dengan opsi `double_encode` diset
ke `TRUE` secara default:

```php
echo e('<html>foo</html>');

// &lt;html&gt;foo&lt;/html&gt;
```

<a id="dd"></a>

### dd

Fungsi `dd` akan meng-dump isi variabel dan eksekusi skrip dihentikan:

```php
dd($value);

dd($value1, $value2, $value3, ...);
```

<a id="dump"></a>

### dump

Fungsi `dump` akan meng-dump isi variabel tetapi eksekusi skrip tetap akan berjalan:

```php
dump($value);

dump($value1, $value2, $value3, ...);
```

<a id="bd"></a>

### bd

Fungsi `bd` akan meng-dump isi variabel ke [debug bar](/docs/id/debugging#debug-bar).
Eksekusi skrip tetap akan berjalan:

```php
bd($value);

bd($value, 'Breakpoint 1');
```

<a id="__"></a>

### \_\_

Fungsi `__` menerjemahkan string berdasarkan data dari file alih-bahasa yang ada di folder `languages`:

```php
echo __('marketing.welcome');
```

<a id="is_cli"></a>

### is_cli

Fungsi `is_cli` memeriksa apakah script dijalankan dari dalam konsol:

```php
if (is_cli()) {
    // Request datang dari command line!
}
```

<a id="data_fill"></a>

### data_fill

Fungsi `data_fill` meng-set value yang hilang dalam array bersarang menggunakan notasi "dot":

```php
$data = ['products' => ['desk' => ['price' => 100]]];

data_fill($data, 'products.desk.price', 200);
// ['products' => ['desk' => ['price' => 100]]]

data_fill($data, 'products.desk.discount', 10);
// ['products' => ['desk' => ['price' => 100, 'discount' => 10]]]
```

Fungsi ini juga menerima karakter `*` (asterisk) sebagai wildcard:

```php
$data = [
    'products' => [
        ['name' => 'Desk 1', 'price' => 100],
        ['name' => 'Desk 2'],
    ],
];

data_fill($data, 'products.*.price', 200);
/*
    [
        'products' => [
            ['name' => 'Desk 1', 'price' => 100],
            ['name' => 'Desk 2', 'price' => 200],
        ],
    ]
*/
```

<a id="data_get"></a>

### data_get

Fungsi `data_get` mengambil value dari array bersarang menggunakan notasi "dot":

```php
$data = ['products' => ['desk' => ['price' => 100]]];

$price = data_get($data, 'products.desk.price');
// 100
```

Fungsi `data_get` juga menerima value default, yang akan direturn jika key yang anda minta tidak ditemukan:

```php
$discount = data_get($data, 'products.desk.discount', 0);
// 0
```

Fungsi ini juga menerima karakter `*` (asterisk) sebagai wildcard:

```php
$data = [
    'product-one' => ['name' => 'Desk 1', 'price' => 100],
    'product-two' => ['name' => 'Desk 2', 'price' => 150],
];

data_get($data, '*.name');
// ['Desk 1', 'Desk 2'];
```

<a id="data_set"></a>

### data_set

Fungsi `data_set` meng-set value dalam array bersarang menggunakan notasi "dot":

```php
$data = ['products' => ['desk' => ['price' => 100]]];

data_set($data, 'products.desk.price', 200);
// ['products' => ['desk' => ['price' => 200]]]
```

Fungsi ini juga menerima karakter `*` (asterisk) sebagai wildcard:

```php
$data = [
    'products' => [
        ['name' => 'Desk 1', 'price' => 100],
        ['name' => 'Desk 2', 'price' => 150],
    ],
];

data_set($data, 'products.*.price', 200);
/*
    [
        'products' => [
            ['name' => 'Desk 1', 'price' => 200],
            ['name' => 'Desk 2', 'price' => 200],
        ],
    ]
*/
```

Secara default, semua value yang ada akan ditimpa. Jika Anda hanya ingin
meng-set value jika ia belum ada, silahkan oper `FALSE` ke parameter keempat:

```php
$data = ['products' => ['desk' => ['price' => 100]]];

data_set($data, 'products.desk.price', 200, $overwrite = false);
// ['products' => ['desk' => ['price' => 100]]]
```

<a id="retry"></a>

### retry

Fungsi `retry` mencoba mengeksekusi callback sebanyak batas percobaan yang diberikan.
Jika mengeksekusi callback tersebut tidak menyebabkan exception,
ia akan mereturn hasil eksekusi callbacknya.

Tapi jika terjadi exception, ia akan mengulangi eksekusinya lagi secara otomatis.
Jika jumlah percobaan sudah mencapai batas yang anda tentukan, ia akan mentrigger exception:

```php
return retry(5, function () {
    // Coba 5 kali dengan jeda selama 100ms di setiap percobaanya...
}, 100);
```

<a id="facile_to_json"></a>

### facile_to_json

Fungsi `facile_to_json` akan mengubah object Facile model menjadi string JSON:

```php
$json = facile_to_json(User::find(1));

$json = facile_to_json(User::all());
```

<a id="head"></a>

### head

Fungsi `head` mereturn elemen pertama dari array yang diberikan:

```php
$array = [100, 200, 300];

$first = head($array);
// 100
```

<a id="last"></a>

### last

Fungsi `last` mereturn elemen terakhir dari array yang diberikan:

```php
$array = [100, 200, 300];

$first = last($array);
// 300
```

<a id="url"></a>

### url

Fungsi `url` menghasilkan URL ke path yang diberikan:

```php
$url = url('user/profile');
// https://situsku.com/index.php/user/profile
```

<a id="asset"></a>

### asset

Fungsi `aset` menghasilkan URL ke aset:

```php
$url = asset('css/style.css');
// https://situsku.com/assets/css/style.css

$url = asset('packages/docs/css/style.css');
// https://situsku.com/assets/packages/docs/css/style.css
```

> Asset bisa berupa gambar, CSS, JavaScript atau file lainnya yang tersimpan di folder `assets/` di root aplikasi.

<a id="action"></a>

### action

Fungsi `action` menghasilkan URL ke action milik controller:

```php
// Buat URL ke action 'index' milik User_Controller
$url = action('user@index');
```

Anda juga dapat mengoper parameter ke URL tujuan melalui method ini:

```php
// Buat URL ke Profil budi
$url = action('user@profile', ['budi']);
```

<a id="route"></a>

### route

Fungsi `route` menghasilkan URL ke [named route](/docs/id/routing#named-route):

```php
// Buat URL ke route yang bernama 'profile'.
$url = route('profile');
```

Seperti halnya action, anda juga dapat mengoper parameter ke URL tujuan melalui method ini:

```php
$url = route('profile', [$username]);
```

<a id="redirect"></a>

### redirect

Fungsi `redirect` mereturn object response redirect:

```php
return redirect($url, $status = 302)

return redirect('/home');
return redirect('/home', 301);
return redirect('https://google.com');

return redirect('/edit')
    ->with('status', 'Profil gagal diubah!');
    ->with_input()
    ->with_errors($validation);
```

<a id="csrf_field"></a>

### csrf_field

Fungsi `csrf_field` menghasilkan field hidden input yang berisi CSRF token:

```php
<?php echo csrf_field() ?>
// <input type="hidden" name="csrf_token" value="Wz5CiADRl2ydbHflMEOFQdoS4bxmd11KlhLNoLmB">
```

<a id="root_namespace"></a>

### root_namespace

Fungsi `root_namespace` mengambil root namespace dari sebuah string kelas:

```php
$data = root_namespace('System\Database\Facle\Model');
// 'System'
```

<a id="class_basename"></a>

### class_basename

Fungsi `class_basename` mengambil nama kelas tanpa namespace:

```php
$data = class_basename('System\Database\Facle\Model');
// 'Model'
```

<a id="value"></a>

### value

Fungsi `value` mereturn nilai yang oper kepadanya. Namun, jika yang dioper adalah Closure,
hasil dari eksekusi closure tersebutlah yang akan dikembalikan:

```php
$result = value(true); // true

$result = value(function () { return false; }); // false
```

<a id="view"></a>

### view

Fungsi `view` mereturn instance kelas `View`:

```php
return view('user.profile');

return view('user.profile')
    ->with('name', 'Angga');
```

<a id="render"></a>

### render

Fungsi `render` mengkompilasi view [Blade](/docs/id/views/templating) menjadi bentuk HTML:

```php
// File: views/home.blade.php
@include('partials.header')

<p>Halo {{ $user->name }}</p>

@include('partials.footer')
```

```php
$rendered = render('home');
// <html><head></head><body><p>Halo Budi</p></body></html>
```

<a id="render_each"></a>

### render_each

Fungsi `render_each` mengkompilasi view blade menjadi bentuk HTML,
namun fungsi ini khusus untuk merender view parsial saja:

```php
$rendered = render_each('partials.header');
// <html><head></head><body>
```

<a id="yield_content"></a>

### yield_content

Fungsi `yield_content` merupakan padanan dari sintaks blade `@yield`:

```php
$content = yield_content('content');
```

<a id="yield_section"></a>

### yield_section

Fungsi `yield_section` merupakan padanan dari sintaks blade `@show`:

```php
$content = yield_section('nama-section');
```

<a id="section_start"></a>

### section_start

Fungsi `section_start` merupakan padanan dari sintaks blade `@section()`:

```php
section_start('nama-section');
// Isi konten secttion disini..
```

<a id="section_stop"></a>

### section_stop

Fungsi `section_stop` merupakan padanan dari sintaks blade `@endsection`:

```php
section_stop();
```

<a id="get_cli_option"></a>

### get_cli_option

Fungsi `get_cli_option` mereturn option di oper user pada rakit console:

```bash
# command
php rakit package:install access --verbose=yes
```

```php
$option = get_cli_option('verbose');
// 'yes'

$option = get_cli_option('foo'); // null
$option = get_cli_option('foo', 'bar'); // 'bar'
```

<a id="system_os"></a>

### system_os

Fungsi `system_os` mereturn sistem operasi server anda:

```php
echo system_os(); // Windows
echo system_os(); // Darwin
echo system_os(); // BSD
echo system_os(); // Linux
echo system_os(); // Unknown
```
