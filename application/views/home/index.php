<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
        <title>Selamat datang!</title>
        <link rel="stylesheet" href="<?php echo asset('home/css/style.min.css'); ?>">
    </head>
    <body>
        <header>
            <h1>RAKIT <?php echo RAKIT_VERSION; ?></h1>
            <h5>Kerangka kerja PHP sederhana, ringan dan modular.</h5>
        </header>

        <h3>Tentang halaman ini.</h3>
        <p>
            Halaman yang sedang anda lihat ini dibuat secara dinamis oleh rakit.
            Jika anda ingin mengedit halaman ini, anda akan menemukannya di:
        </p>
        <pre>application/views/home/index.php</pre>

        <p>Dan rute yang menangani halaman ini dapat ditemukan di:</p>
        <pre>application/routes.php</pre>

        <br>

        <h3>Melangkah lebih jauh.</h3>
        <p>
            Halaman dokumentasi berisi pendahuluan, tutorial serta referensi fitur bawaan rakit.
            Jangan ragu untuk mulai membaca
            <a href="<?php echo url('docs') ?>" target="_blank">halaman dokumentasi</a>.
        </p>

        <br>

        <h3>Ciptakan sesuatu yang indah.</h3>
        <p>
            Sekarang setelah instalasi berjalan, saatnya untuk mulai mencipta!
            Berikut adalah beberapa tautan untuk membantu anda memulai:
        </p>
        <ul class="none">
            <li><a href="https://rakit.esyede.my.id" target="_blank">Situs Resmi</a></li>
            <li><a href="https://rakit.esyede.my.id/api" target="_blank">Referensi API</a></li>
            <li><a href="https://rakit.esyede.my.id/repositories" target="_blank">Repositori Paket</a></li>
            <li><a href="https://rakit.esyede.my.id/forum" target="_blank">Forum Diskusi</a></li>
            <li><a href="https://github.com/laravel/laravel">Kode Sumber</a></li>
        </ul>

        <br>
        <br>
        <br>

    </body>
</html>
