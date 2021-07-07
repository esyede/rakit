# Array

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [List Helper](#list-helper)
    - [Arr::accessible\(\)](#arraccessible)
    - [Arr::add\(\)](#arradd)
    - [Arr::collapse\(\)](#arrcollapse)
    - [Arr::divide\(\)](#arrdivide)
    - [Arr::dot\(\)](#arrdot)
    - [Arr::except\(\)](#arrexcept)
    - [Arr::exists\(\)](#arrexists)
    - [Arr::first\(\)](#arrfirst)
    - [Arr::flatten\(\)](#arrflatten)
    - [Arr::forget\(\)](#arrforget)
    - [Arr::get\(\)](#arrget)
    - [Arr::has\(\)](#arrhas)
    - [Arr::associative\(\)](#arrassociative)
    - [Arr::last\(\)](#arrlast)
    - [Arr::only\(\)](#arronly)
    - [Arr::pluck\(\)](#arrpluck)
    - [Arr::prepend\(\)](#arrprepend)
    - [Arr::pull\(\)](#arrpull)
    - [Arr::random\(\)](#arrrandom)
    - [Arr::set\(\)](#arrset)
    - [Arr::shuffle\(\)](#arrshuffle)
    - [Arr::sort\(\)](#arrsort)
    - [Arr::recsort\(\)](#arrrecsort)
    - [Arr::where\(\)](#arrwhere)
    - [Arr::wrap\(\)](#arrwrap)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Komponen ini menyertakan berbagai helper untuk membuat hidup anda lebih mudah saat bekerja dengan array.
Berikut adalah daftar helper yang tersedia untuk anda:


<a id="list-helper"></a>
## List Helper

Berikut dalah daftar helper yang tersedia untuk komponen ini:


<a id="arraccessible"></a>
### Arr::accessible()

Metohd ini memeriksa bahwa value yang diberikan merupakan array yang dapat diakses:

```php
return Arr::accessible(['a' => 1, 'b' => 2]); // true
return Arr::accessible('abc');                // false
return Arr::accessible(new \stdClass());      // false
```


<a id="arradd"></a>
### Arr::add()

Method ini menambahkan pasangan key / value tertentu ke array jika key yang diberikan
belum ada di array atau disetel ke `NULL`:

```php
return Arr::add(['name' => 'Desk'], 'price', 100);
// ['name' => 'Desk', 'price' => 100]

return Arr::add(['name' => 'Desk', 'price' => null], 'price', 100);
// ['name' => 'Desk', 'price' => 100]
```


<a id="arrcollapse"></a>
### Arr::collapse()

Metode ini menciutkan array multi-dimensi menjadi array tunggal:

```php
return Arr::collapse([[1, 2, 3], [4, 5, 6], [7, 8, 9]]);
// [1, 2, 3, 4, 5, 6, 7, 8, 9]
```


<a id="arrdivide"></a>
### Arr::divide()

Method ini mereturn dua buah array, satu berisi key, dan yang lainnya berisi
value dari array yang diberikan:

```php
list($keys, $values) = Arr::divide(['name' => 'Desk']);
// $keys: ['name']
// $values: ['Desk']
```


<a id="arrdot"></a>
### Arr::dot()

Method ini meratakan array multi-dimensi menjadi array tunggal yang menggunakan notasi "dot"
untuk menunjukkan kedalaman array:

```php

$array = ['products' => ['desk' => ['price' => 100]]];

return Arr::dot($array); // ['products.desk.price' => 100]
```


<a id="arrexcept"></a>
### Arr::except()

Method ini menghapus pasangan key / value tertentu dari array:

```php
$array = ['name' => 'Desk', 'price' => 100];

return Arr::except($array, ['price']); // ['name' => 'Desk']
```


<a id="arrexists"></a>
### Arr::exists()

Method ini memeriksa bahwa key yang diberikan ada dalam sebuah array:

```php
$array = ['name' => 'Agung', 'age' => 17];

return Arr::exists($array, 'name');   // true
return Arr::exists($array, 'salary'); // false
```


<a id="arrfirst"></a>
### Arr::first()

Method ini mereturn elemen pertama dari sebuah array yang lolos dari uji kebenaran yang diberikan:

```php
$array = [100, 200, 300];

return Arr::first($array, function ($value, $key) {
    return $value >= 150;
});

// 200
```

Default value juga dapat diberikan sebagai parameter ketiga untuk methog ini. Value ini akan
direturn jika tidak ada value yang lolos dari uji kebenaran yang anda berikan:

```php
return Arr::first($array, $callback, $default);
```

<a id="arrflatten"></a>
### Arr::flatten()

Method ini meratakan array multi-dimensi menjadi array tunggal:

```php
$array = ['name' => 'Dimas', 'languages' => ['PHP', 'Ruby']];

return Arr::flatten($array); // ['Dimas', 'PHP', 'Ruby']
```


<a id="arrforget"></a>
### Arr::forget()

Method ini menghapus pasangan key / value tertentu dari array menggunakan notasi "dot":

```php
$array = ['products' => ['desk' => ['price' => 100]]];

Arr::forget($array, 'products.desk');

return $array; // ['products' => []]
```


<a id="arrget"></a>
### Arr::get()

Method ini mengambil sebuah value dari array menggunakan notasi "dot":

```php
$array = ['products' => ['desk' => ['price' => 100]]];

return Arr::get($array, 'products.desk.price'); // 100
```

Method ini juga menerima default value, yang akan direturn jika key yang diminta tidak ditemukan:

```php
return Arr::get($array, 'products.desk.discount', 0); // 0
```


<a id="arrhas"></a>
### Arr::has()

Method ini memeriksa apakah item tertentu ada dalam array menggunakan notasi "dor":

```php

$array = ['product' => ['name' => 'Desk', 'price' => 100]];

return Arr::has($array, 'product.name'); // true
return Arr::has($array, ['product.price', 'product.discount']); // false
```


<a id="arrassociative"></a>
### Arr::associative()

Method ini mereturn `TRUE` jika array yang diberikan adalah array asosiatif. Sebuah array
akan dianggap "asosiatif" jika ia tidak memiliki key numerik berurutan yang dimulai dengan nol:

```php
$array1 = ['product' => ['name' => 'Desk', 'price' => 100]];
$array2 = [1, 2, 3];

return Arr::associative($array1); // true
return Arr::associative($array2); // false
```


<a id="arrlast"></a>
### Arr::last()

Method ini mereturn elemen terakhir dari sebuah array yang lolos uji kebenaran yang diberikan:

```php
$array = [100, 200, 300, 110];

return Arr::last($array, function ($value, $key) {
    return $value >= 150;
});

// 300
```

Default value dapat ditambahkan sebagai parameter ketiga untuk method ini.
Value ini akan di-return jika tidak ada value yang lolos dari uji kebenaran yang anda berikan:

```php
return Arr::last($array, $callback, $default);
```


<a id="arronly"></a>
### Arr::only()

Method ini hanya mereturn pasangan key / value yang ditentukan dari array yang diberikan:

```php
$array = ['name' => 'Desk', 'price' => 100, 'orders' => 10];

return Arr::only($array, ['name', 'price']);
// ['name' => 'Desk', 'price' => 100]
```


<a id="arrpluck"></a>
### Arr::pluck()

Metode ini mengambil semua value untuk milik key tertentu dari array:

```php
$array = [
    ['developer' => ['id' => 1, 'name' => 'Budi']],
    ['developer' => ['id' => 2, 'name' => 'Sarah']],
];

return Arr::pluck($array, 'developer.name');
// ['Budi', 'Sarah']
```

Anda juga dapat menentukan bagaimana bentuk key output array yang dihasilkan:

```php
return Arr::pluck($array, 'developer.name', 'developer.id');
// [1 => 'Budi', 2 => 'Sarah']
```


<a id="arrprepend"></a>
### Arr::prepend()

Method ini akan menambahkan sebuah item ke bagian awal sebuah array:

```php
$array = ['one', 'two', 'three', 'four'];

$array = Arr::prepend($array, 'zero');
// ['zero', 'one', 'two', 'three', 'four']
```

Jika diperlukan, anda juga boleh menentukan key mana yang harus dipakai untuk si item:

```php
$array = ['price' => 100];

$array = Arr::prepend($array, 'Desk', 'name');
// ['name' => 'Desk', 'price' => 100]
```


<a id="arrpull"></a>
### Arr::pull()

Metode ini mereturn dan menghapus pasangan key / value dari sebuah array:

```php
$array = ['name' => 'Desk', 'price' => 100];

$name = Arr::pull($array, 'name');
// $name: 'Desk'
// $array: ['price' => 100]
```

Default value dapat diberikan sebagai parameter ketiga untuk method ini. Value ini akan
di-return jika key yang anda mau tidak ditemukan:

```php
$value = Arr::pull($array, $key, $default);
```


<a id="arrrandom"></a>
### Arr::random()

Method ini mereturn sebuah value acak dari array:

```php
$array = [1, 2, 3, 4, 5];

return Arr::random($array); // 4 - (diperoleh secara acak)
```

Anda juga dapat menentukan berapa banyak item yang harus di-return melalui parameter ketiga.

Patut diperhatikan bahwa jika opsi ini digunakan, return value yang anda dapat akan selalu berupa array.

```php
return Arr::random($array, 2); // [2, 5] - (diperoleh secara acak)
```


<a id="arrset"></a>
### Arr::set()

Method ini digunakan untuk menetapkan sebuah value ke array menggunakan notasi "dot":

```php
$array = ['products' => ['desk' => ['price' => 100]]];

Arr::set($array, 'products.desk.price', 200);

// $array: ['products' => ['desk' => ['price' => 200]]]
```


<a id="arrshuffle"></a>
### Arr::shuffle()

Method ini mengacak item milik sebuah array:

```php
return Arr::shuffle([1, 2, 3, 4, 5]);
// [3, 2, 5, 1, 4] - (dibuat secara acak)
```


<a id="arrsort"></a>
### Arr::sort()

Method ini mengurutkan array berdasarkan valuenya:

```php
$array = ['Desk', 'Table', 'Chair'];

return Arr::sort($array);
// ['Chair', 'Desk', 'Table']
```

Anda juga dapat mengurutkan array menggunakan Closure:

```php
$array = [
    ['name' => 'Desk'],
    ['name' => 'Table'],
    ['name' => 'Chair'],
];

return array_values(Arr::sort($array, function ($value) {
    return $value['name'];
}));

/**
    [
        ['name' => 'Chair'],
        ['name' => 'Desk'],
        ['name' => 'Table'],
    ]
*/
```


<a id="arrrecsort"></a>
### Arr::recsort()

Method ini mengurutkan array secara rekursif menggunakan
bantuan fungsi [sort](https://php.net/manual/en/function.sort.php)
untuk sub-array numerik, dan [ksort](https://php.net/manual/en/function.ksort.php)
untuk sub-array asosiatif:

```php
$array = [
    ['Roman', 'Budi', 'Li'],
    ['PHP', 'Ruby', 'JavaScript'],
    ['one' => 1, 'two' => 2, 'three' => 3],
];

return Arr::recsort($array);

/**
    [
        ['JavaScript', 'PHP', 'Ruby'],
        ['one' => 1, 'three' => 3, 'two' => 2],
        ['Li', 'Roman', 'Budi'],
    ]
*/
```


<a id="arrwhere"></a>
### Arr::where()

Method ini dipakai untuk menyaring array menggunakan Closure:

```php
$array = [100, '200', 300, '400', 500];

return Arr::where($array, function ($value, $key) {
    return is_string($value);
});

// [1 => '200', 3 => '400']
```


<a id="arrwrap"></a>
### Arr::wrap()

Method ini membungkus value yang diberikan dalam sebuah array. Jika value yang diberikan
sudah berupa array, value tersebut tidak akan diubah:

```php
$string = 'Bakso';

return Arr::wrap($string); // ['Bakso']
```

Jika value yang diberikan adalah `NULL`, ia akan mereturn `array kosong`:

```php
$kosong = null;

return Arr::wrap($kosong); // []
```

> Beberapa helper tambahan untuk operasi array juga tersedia di halaman [Helper](/docs/helpers)
