# Job

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Cara Penggunaan](#cara-penggunaan)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Komponen ini digunakan untuk mengantrekan event ke database untuk kemudian
menjalankannya melalui konsol rakit atau cronjob unix.

Ini akan berguna ketika anda perlu, misalnya, menjadwalkan email ke pengguna 24 jam setelah pendaftaran.

```php
$name         = 'email24';
$payloads     = ['user_id' => 1, 'message' => 'Welcome new member!'];
$scheduled_at = now()->addDay();

Job::add($name, $payloads, $scheduled_at);
```

<a id="cara-penggunaan"></a>

## Cara Penggunaan

Untuk menggunakan komponen ini, anda perlu membuat tabel `'jobs'`:

```bash
php rakit job:table
```

Kemudian:

```bash
php rakit migrate
```

Sekarang, komponen sudah siap digunakan. Contohnya:

```php
Route::get('execute-my-event', function () {
    // Cara umum:
    // Event::fire('log-something', ['foo', ['bar' => 'baz']]);

    // Dengan job:
    Job::add('log-something', ['foo', ['bar' => 'baz']]);
});

// Eventnya
Event::listen('log-something', function ($foo) {
    Log::info(sprintf('Foo value is: %s', json_encode($foo)));
});
```

Tambahkan baris berikut ke crontab:

```bash
*/1 * * * * php /var/www/mysite/rakit job:run log-something

# atau,

*/1 * * * * php /var/www/mysite/rakit job:runall
```

Atau langsung jalankan commandnya:

```bash
php rakit job:run log-something
```

Mantap! si job akan menjalankan event `log-something` dalam urutan [FIFO](http://en.wikipedia.org/wiki/FIFO).

Jika diperlukan, anda juga bisa menjalankan seluruh job yang terdaftar di database:

```bash
php rakit job:runall
```

Anda juga boleh menjalankannya via route, cukup ubah file `job/config/job.php` untuk mengizinkannya:

```php
'cli_only' => false,
```

Dan kemudian buat routingnya:

```php
Route::get('execute-log-something', function () {
    // Jangan lupa proteksi route ini..
    $secret_key = Hash::make('s3cr3t');

    if (! Hash::check(Input::query('key'), $secret_key)) {
        return Response::json([
            'success' => false,
            'message' => 'Unauthorized: wrong secret key!',
        ], 401);
    }

    $result = Job::run('log-something');

    return Response::json([
        'success' => $result,
        'message' => 'Executing log-something: '.($result ? 'success' : 'failed'),
    ]);
});
```

Kunjungi ` https://mysite.com/execute-log-something?key=s3cr3t` untuk menjalankan jobnya.
