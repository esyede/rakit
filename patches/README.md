# patches

Patch untuk dependensi `require-dev` (PHPUnit 4.8.34 dan turunannya) agar tetap
bisa dipakai dari PHP 5.4 sampai PHP 8.5. Patch diterapkan otomatis oleh
[`cweagans/composer-patches`](https://github.com/cweagans/composer-patches)
saat `composer install`, sesuai daftar di `extra.patches` pada `composer.json`.

| Berkas | Paket | Isi |
| --- | --- | --- |
| `phpunit_php84.patch` | `phpunit/phpunit` | Konstanta `E_STRICT` yang usang di PHP 8.4, dan parameter *implicitly nullable* pada `PHPUnit_Framework_Error` / `PHPUnit_Framework_Exception`. Kedua kelas itu dikompilasi sebelum bootstrap sempat mematikan `E_DEPRECATED`, jadi peringatannya selalu tercetak di setiap kali test dijalankan. Type hint `Exception` dihapus (bukan diubah jadi `?Exception`) karena paketnya masih harus bisa dimuat di PHP 5.4. |
| `phpunit_php_token_stream.patch` | `phpunit/php-token-stream` | PHP 8 menambah token yang tidak punya kelas `PHP_Token_*` (`T_NAME_FULLY_QUALIFIED`, `T_NAME_QUALIFIED`, `T_ATTRIBUTE`, `T_MATCH`, `T_READONLY`, `T_PUBLIC_SET`, ...), sehingga `PHP_Token_Stream::scan()` berhenti dengan `Class PHP_Token_... not found` dan semua opsi `--coverage` tidak bisa dipakai. Kelas token PHP 8.0–8.4 ditambahkan, plus fallback ke `PHP_Token_UNKNOWN` supaya token baru di versi PHP berikutnya tidak menggagalkan pemindaian. |

Patch lainnya (`phpunit_mock_objects`, `phpunit_path_file_iterator`,
`phpunit_php7`, `phpunit_php8`, `phpunit_php81`) diambil dari
<https://github.com/esyede/phpunit-patches>.

Semua patch di folder ini memakai jalur relatif terhadap akar paket, jadi
diterapkan dengan `-p1`.
